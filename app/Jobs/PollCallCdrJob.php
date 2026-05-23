<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Jobs\UpdateCampaignStatus;
use App\Models\Asterisk\Cdr;
use App\Models\Call;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PollCallCdrJob implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum poll attempts before we consider giving up — but we still verify
     * the channel is truly gone via ARI before marking Failed.
     */
    public const int MAX_ATTEMPTS = 30;

    public function __construct(public readonly int $callId) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $settledCampaignId = null;

        DB::transaction(function () use (&$settledCampaignId): void {

            $call = Call::query()
                ->whereKey($this->callId)
                ->whereStatus(CallStatus::Processing)
                ->whereNotNull('unique_id')
                ->with(['caller.server'])
                ->lockForUpdate()
                ->first();

            if (! $call || ! $call->caller?->server) {
                return;
            }

            $server = $call->caller->server;

            $cdr = Cdr::using(
                $server->database_host,
                $server->database_username,
                $server->database_password
            )
                ->where('uniqueid', $call->unique_id)
                ->first();

            $attempt = (int) ($call->poll_attempt ?? 0) + 1;

            if (! empty($cdr)) {
                $this->markAsCompleted($call, $cdr);
                $settledCampaignId = $call->campaign_id;

                return;
            }

            // At each segment checkpoint, verify whether the channel is still alive via ARI.
            // Channel gone → CDR will never appear; refund immediately.
            // Channel alive → call still in progress; keep polling regardless of attempt count.
            if (in_array($attempt, [3, 6, 12, self::MAX_ATTEMPTS], true)) {
                if ($this->isChannelStillActive($call)) {
                    Log::info('CDR not found at poll checkpoint but channel still active, continuing', [
                        'call_id' => $call->id,
                        'unique_id' => $call->unique_id,
                        'attempt' => $attempt,
                    ]);

                    // Reset counter at the final checkpoint so it never exceeds MAX_ATTEMPTS.
                    $nextAttempt = $attempt >= self::MAX_ATTEMPTS ? 0 : $attempt;
                    $this->markAsPending($call, $nextAttempt);

                    return;
                }

                $this->markAsFailed($call, $attempt);
                $settledCampaignId = $call->campaign_id;

                return;
            }

            $this->markAsPending($call, $attempt);
        });

        if ($settledCampaignId) {
            UpdateCampaignStatus::dispatch($settledCampaignId);
        }
    }

    /**
     * Check ARI to see whether the channel for this call is still active.
     *
     * Returns true when ARI reports the channel exists (call still in progress).
     * Returns false when ARI returns 404 (channel gone) or on any error — we
     * treat errors as "gone" to avoid infinite polling; 1.5 h of CDR-missing
     * attempts before a checkpoint fires is enough margin for transient outages.
     */
    private function isChannelStillActive(Call $call): bool
    {
        try {
            $response = $call->caller->server->httpClient()
                ->get("ari/channels/{$call->unique_id}");

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('ARI channel check failed during poll timeout', [
                'call_id' => $call->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function markAsCompleted(Call $call, Cdr $cdr): void
    {
        $actualDuration = (int) $cdr->billsec;

        $user = User::query()
            ->whereKey($call->user_id)
            ->lockForUpdate()
            ->first();

        if (! $user) {
            return;
        }

        $actualCost = $this->calculateEstimatedCost($actualDuration, $user);
        $estimatedCost = (float) $call->cost;

        $diff = (float) $actualCost - $estimatedCost;

        // update call
        $call->update([
            'status' => CallStatus::Completed,
            'duration' => $actualDuration,
            'cost' => $actualCost,
            'poll_attempt' => null,
            'next_poll_at' => null,
        ]);

        // no change needed (tolerate float noise within 1 paisa)
        if (abs($diff) < 0.01) {
            return;
        }

        $before = $user->balance;

        if ($diff > 0) {
            // undercharged → collect extra
            $user->decrement('balance', $diff);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Debit,
                'amount' => $diff,
                'balance_before' => $before,
                'balance_after' => $before - $diff,
                'currency' => 'BDT',
                'description' => "Extra charge adjustment for call #{$call->id}",
            ]);
        } else {
            // overcharged → refund difference
            $refund = abs($diff);

            $user->increment('balance', $refund);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Credit,
                'amount' => $refund,
                'balance_before' => $before,
                'balance_after' => $before + $refund,
                'currency' => 'BDT',
                'description' => "Refund adjustment for call #{$call->id}",
            ]);
        }
    }

    private function markAsFailed(Call $call, int $attempt): void
    {
        if ($call->cost <= 0) {
            $call->update([
                'status' => CallStatus::Failed,
                'poll_attempt' => $attempt,
                'next_poll_at' => null,
            ]);

            return;
        }

        $user = User::query()
            ->whereKey($call->user_id)
            ->lockForUpdate()
            ->first();

        if (! $user) {
            return;
        }

        $before = $user->balance;

        $user->increment('balance', $call->cost);

        $call->transactions()->create([
            'user_id' => $user->id,
            'type' => TransactionType::Credit,
            'amount' => $call->cost,
            'balance_before' => $before,
            'balance_after' => $before + $call->cost,
            'currency' => 'BDT',
            'description' => "Refund for call #{$call->id} (CDR not found after {$attempt} attempts)",
        ]);

        $call->update([
            'status' => CallStatus::Failed,
            'poll_attempt' => $attempt,
            'next_poll_at' => null,
            'cost' => 0,
        ]);
    }

    private function markAsPending(Call $call, int $attempt): void
    {
        $call->update([
            'poll_attempt' => $attempt,
            'next_poll_at' => now()->addSeconds($this->delayForAttempt($attempt)),
        ]);
    }

    /**
     * Polling cadence with ARI segment checkpoints at attempts 3, 6, 12, and MAX_ATTEMPTS:
     *
     *   attempts 1–3   →  30s each   checkpoint @ 3  (~1.5 min)
     *   attempts 4–6   →  60s each   checkpoint @ 6  (+3 min)
     *   attempts 7–12  →  120s each  checkpoint @ 12 (+12 min)
     *   attempts 13–30 →  300s each  checkpoint @ 30 (+1.5 h)
     */
    private function delayForAttempt(int $attempt): int
    {
        return match (true) {
            $attempt <= 3 => 30,
            $attempt <= 6 => 60,
            $attempt <= 12 => 120,
            default => 300,
        };
    }

    private function calculateEstimatedCost(int $duration, User $user): int|float
    {
        $pulseDuration = $user->pulse_duration ?? 60;
        $pulseRate = $user->pulse_rate ?? 0;

        if ($pulseDuration <= 0 || $pulseRate <= 0 || $duration <= 0) {
            return 0;
        }

        $pulses = (int) ceil($duration / $pulseDuration);

        return $pulses * $pulseRate;
    }
}

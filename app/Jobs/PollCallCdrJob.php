<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Asterisk\Cdr;
use App\Models\Call;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PollCallCdrJob implements ShouldQueue
{
    use Queueable;

    public const int MAX_ATTEMPTS = 7;

    public function __construct(public readonly int $callId) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {

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

            $cdrExists = ! empty($cdr);

            $attempt = (int) ($call->poll_attempt ?? 0) + 1;
            $exceedAttempts = $attempt >= self::MAX_ATTEMPTS;

            match (true) {
                $cdrExists => $this->markAsCompleted($call, $cdr),
                $exceedAttempts => $this->markAsFailed($call, $attempt),
                default => $this->markAsPending($call, $attempt),
            };
        });
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
        $estimatedCost = (int) $call->cost;

        $diff = $actualCost - $estimatedCost;

        // update call
        $call->update([
            'status' => CallStatus::Completed,
            'duration' => $actualDuration,
            'cost' => $actualCost,
            'poll_attempt' => null,
            'next_poll_at' => null,
        ]);

        // no change needed
        if ($diff === 0) {
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

    private function delayForAttempt(int $attempt): int
    {
        return match (true) {
            $attempt <= 3 => 30,
            $attempt <= 5 => 60,
            default => 120,
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

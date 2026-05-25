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
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReconcileStaleCall implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $callId
    ) {}

    public function handle(): void
    {
        $campaignId = null;

        DB::transaction(function () use (&$campaignId): void {
            $call = Call::query()
                ->withoutGlobalScopes()
                ->with(['caller.server'])
                ->whereKey($this->callId)
                ->lockForUpdate()
                ->first();

            if (! $call) {
                Log::warning('ReconcileStaleCall: call not found', ['call_id' => $this->callId]);

                return;
            }

            // Only reconcile calls still in Processing.
            if ($call->status !== CallStatus::Processing) {
                return;
            }

            $campaignId = $call->campaign_id;

            if (! $call->unique_id) {
                $this->refund($call);

                return;
            }

            if (! $call->caller?->server) {
                $this->refund($call);

                return;
            }

            $server = $call->caller->server;

            $cdr = Cdr::using(
                host: $server->database_host,
                username: $server->database_username,
                password: $server->database_password
            )
                ->where('uniqueid', $call->unique_id)
                ->first();

            if (! $cdr || $cdr->billsec <= 0) {
                $this->refund($call);

                return;
            }

            $this->complete($call, $cdr->billsec);
        });

        if ($campaignId) {
            UpdateCampaignStatus::dispatch($campaignId);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ReconcileStaleCall job failed', [
            'call_id' => $this->callId,
            'exception' => $exception->getMessage(),
        ]);
    }

    private function refund(Call $call): void
    {
        $cost = (float) $call->cost;

        if ($cost > 0) {
            $user = User::query()
                ->whereKey($call->user_id)
                ->lockForUpdate()
                ->first();

            if ($user) {
                $before = (float) $user->balance;

                $user->increment('balance', $cost);

                $call->transactions()->create([
                    'user_id' => $user->id,
                    'type' => TransactionType::Credit,
                    'amount' => $cost,
                    'balance_before' => $before,
                    'balance_after' => $before + $cost,
                    'currency' => 'BDT',
                    'description' => "Refund for stale call #{$call->id}",
                ]);
            }
        }

        $call->update(['status' => CallStatus::Failed, 'cost' => 0]);
    }

    private function complete(Call $call, int $billSec): void
    {
        $user = User::query()
            ->whereKey($call->user_id)
            ->lockForUpdate()
            ->first();

        if (! $user) {
            return;
        }

        $actualCost = $this->calculateCost($billSec, $user);
        $estimatedCost = (float) $call->cost;
        $diff = $actualCost - $estimatedCost;

        $call->update([
            'status' => CallStatus::Completed,
            'duration' => $billSec,
            'cost' => $actualCost,
        ]);

        if (abs($diff) < 0.01) {
            return;
        }

        $before = (float) $user->balance;

        if ($diff > 0) {
            $user->decrement('balance', $diff);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Debit,
                'amount' => $diff,
                'balance_before' => $before,
                'balance_after' => $before - $diff,
                'currency' => 'BDT',
                'description' => "Extra charge adjustment for stale call #{$call->id}",
            ]);
        } else {
            $refund = abs($diff);
            $user->increment('balance', $refund);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Credit,
                'amount' => $refund,
                'balance_before' => $before,
                'balance_after' => $before + $refund,
                'currency' => 'BDT',
                'description' => "Refund adjustment for stale call #{$call->id}",
            ]);
        }
    }

    private function calculateCost(int $duration, User $user): float
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

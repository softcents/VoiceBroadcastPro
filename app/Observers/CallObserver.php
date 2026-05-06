<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CallObserver
{
    /**
     * Handle the Call "created" event.
     *
     * @throws Throwable
     */
    public function created(Call $call): void
    {
        if ($call->campaign_id) {
            // Campaign calls are handled in CampaignObserver
            return;
        }

        DB::transaction(function () use ($call): void {
            $lockedCall = Call::whereKey($call->id)
                ->lockForUpdate()
                ->firstOrFail();

            $user = User::whereKey($lockedCall->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->reserveCost($lockedCall, $user);

            // Use saveQuietly to avoid re-triggering the updated() handler.
            // The Pending status here represents "reserved and ready to dial".
            $lockedCall->status = CallStatus::Pending;
            $lockedCall->saveQuietly();
        });
    }

    /**
     * Handle the Call "updated" event.
     *
     * @throws Throwable
     */
    public function updated(Call $call): void
    {
        if (! $call->wasChanged('status')) {
            return;
        }

        $newStatus = $call->status;

        DB::transaction(function () use ($call, $newStatus): void {
            $lockedCall = Call::whereKey($call->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Guard: status may have changed again between event and lock acquisition.
            if ($lockedCall->status !== $newStatus) {
                Log::warning('CallObserver: stale status transition skipped', [
                    'call_id' => $lockedCall->id,
                    'event_status' => $newStatus->value,
                    'current_status' => $lockedCall->status->value,
                ]);

                return;
            }

            $user = User::whereKey($lockedCall->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            match (true) {
                $newStatus->isCompleted() => $this->settleCompleted($lockedCall, $user),
                $newStatus->isRefundable() => $this->refundFull($lockedCall, $user),
                default => null,
            };
        });
    }

    /**
     * Reserve the estimated cost of a call against the user's balance.
     *
     * @throws Exception
     */
    private function reserveCost(Call $call, User $user): void
    {
        $call->loadMissing('audio');
        $audio = $call->audio;

        if (! $audio) {
            throw new Exception('Audio not found');
        }

        // Cost should be stored as integer minor units (e.g. paisa) to avoid
        // floating-point drift. calculateCostForUser() must return int.
        $cost = $audio->calculateCostForUser($user);

        if ($cost <= 0) {
            return;
        }

        if ($user->balance < $cost) {
            throw new Exception(
                "User {$user->id} has insufficient balance to reserve call {$call->id}."
            );
        }

        $user->decrement('balance', $cost);

        $this->recordTransaction(
            user: $user,
            call: $call,
            type: TransactionType::Debit,
            amount: $cost,
            description: "Reserved balance for call ID {$call->id}",
        );

        $call->cost = $cost;
        $call->saveQuietly();
    }

    /**
     * Settle a completed call: charge or refund the difference between
     * reserved cost and actual cost based on duration.
     */
    private function settleCompleted(Call $call, User $user): void
    {
        $pulseDuration = (int) $user->pulse_duration;
        $pulseRate = (int) $user->pulse_rate;
        $duration = (int) $call->duration;

        if ($pulseDuration <= 0 || $duration <= 0) {
            // Misconfigured user or zero-duration completion. Treat as no-op
            // and leave the reserved cost as-is. Caller may want to refund;
            // surface this via logs to catch config drift.
            Log::warning('CallObserver: cannot settle completed call', [
                'call_id' => $call->id,
                'pulse_duration' => $pulseDuration,
                'duration' => $duration,
            ]);

            return;
        }

        $pulses = (int) ceil($duration / $pulseDuration);
        $actualCost = $pulses * $pulseRate;
        $reservedCost = (int) $call->cost;

        if ($actualCost === $reservedCost) {
            return;
        }

        if ($actualCost > $reservedCost) {
            $diff = $actualCost - $reservedCost;

            if ($user->balance < $diff) {
                // Best-effort: log and cap actual cost at what the user can pay.
                // Alternative policy: throw and let the caller decide.
                Log::warning('CallObserver: insufficient balance for overage', [
                    'call_id' => $call->id,
                    'shortfall' => $diff - $user->balance, 0,
                ]);
            }

            $user->decrement('balance', $diff);
            $this->recordTransaction(
                user: $user,
                call: $call,
                type: TransactionType::Debit,
                amount: $diff,
                description: "Additional charge for call ID {$call->id} ({$duration}s)",
            );
        } else {
            $diff = $reservedCost - $actualCost;
            $user->increment('balance', $diff);
            $this->recordTransaction(
                user: $user,
                call: $call,
                type: TransactionType::Credit,
                amount: $diff,
                description: "Partial refund for call ID {$call->id} ({$duration}s)",
            );
        }

        $call->cost = $actualCost;
        $call->saveQuietly();
    }

    /**
     * Refund the full reserved cost for failed/busy/not-answered calls.
     */
    private function refundFull(Call $call, User $user): void
    {
        $refundAmount = $call->cost;

        if ($refundAmount <= 0) {
            return;
        }

        $user->increment('balance', $refundAmount);

        $this->recordTransaction(
            user: $user,
            call: $call,
            type: TransactionType::Credit,
            amount: $refundAmount,
            description: "Full refund for {$call->status->value} call ID {$call->id}",
        );

        $call->cost = 0;
        $call->saveQuietly();
    }

    private function recordTransaction(
        User $user,
        Call $call,
        TransactionType $type,
        float $amount,
        string $description,
    ): void {
        $user->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'currency' => 'BDT',
            'description' => $description,
            'reference_type' => Call::class,
            'reference_id' => $call->id,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
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

            $lockedCall->updateQuietly(['status' => CallStatus::Pending]);
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
            amount: $cost,
            description: "Reserved balance for call ID {$call->id}",
        );

        $call->cost = $cost;
        $call->saveQuietly();
    }

    private function recordTransaction(
        User $user,
        Call $call,
        float $amount,
        string $description,
    ): void {
        $user->transactions()->create([
            'type' => TransactionType::Debit,
            'amount' => $amount,
            'currency' => 'BDT',
            'description' => $description,
            'transactionable_type' => Call::class,
            'transactionable_id' => $call->id,
        ]);
    }
}

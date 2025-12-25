<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\Transaction;
use Exception;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CampaignObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Campaign "created" event.
     *
     * @throws Throwable
     */
    public function created(Campaign $campaign): void
    {
        $campaign->loadMissing(['phonebook.contacts', 'audio', 'user']);

        $phonebook = $campaign->phonebook;
        if (! $phonebook || $phonebook->contacts->isEmpty()) {
            return;
        }

        $cost = $campaign->audio->calculateCostForUser($campaign->user);
        $now = now();
        $contacts = $phonebook->contacts;

        // Calculate total cost upfront
        $totalCost = $cost * $contacts->count();

        // Check if user has sufficient balance
        if ($campaign->user->balance < $totalCost) {
            throw new Exception('Insufficient balance');
        }

        DB::transaction(function () use ($campaign, $contacts, $cost, $now, $totalCost) {
            // Bulk insert calls
            $callsData = $contacts->map(function ($contact) use ($campaign, $cost, $now) {
                return [
                    'user_id' => $campaign->user_id,
                    'campaign_id' => $campaign->id,
                    'caller_id' => $campaign->caller_id,
                    'audio_id' => $campaign->audio_id,
                    'contact_id' => $contact->id,
                    'phone_number' => $contact->phone_number,
                    'status' => CallStatus::Pending,
                    'type' => CallType::Marketing,
                    'scheduled_at' => $campaign->scheduled_at,
                    'cost' => $cost,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            // Insert calls in chunks to avoid memory issues
            foreach (array_chunk($callsData, 1000) as $chunk) {
                Call::insert($chunk);
            }

            // Get the inserted call IDs (for transaction references if needed)
            $insertedCalls = Call::where('campaign_id', $campaign->id)
                ->whereBetween('created_at', [$now->copy()->subSecond(), $now->copy()->addSecond()])
                ->get(['id']);

            // Create transactions in bulk
            $transactionsData = $insertedCalls->map(function ($call) use ($cost, $now) {
                return [
                    'user_id' => $call->user_id ?? auth()->id(),
                    'type' => TransactionType::Debit,
                    'amount' => $cost,
                    'currency' => 'BDT',
                    'description' => "Call charge for call ID {$call->id}",
                    'reference_type' => Call::class,
                    'reference_id' => $call->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            // Insert transactions in chunks
            foreach (array_chunk($transactionsData, 1000) as $chunk) {
                Transaction::insert($chunk);
            }

            // Deduct total balance from user in a single update
            $campaign->user->decrement('balance', $totalCost);
        });
    }

    /**
     * Handle the Campaign "updated" event.
     */
    public function updated(Campaign $campaign): void
    {
        //
    }

    /**
     * Handle the Campaign "deleted" event.
     */
    public function deleted(Campaign $campaign): void
    {
        //
    }

    /**
     * Handle the Campaign "restored" event.
     */
    public function restored(Campaign $campaign): void
    {
        //
    }

    /**
     * Handle the Campaign "force deleted" event.
     */
    public function forceDeleted(Campaign $campaign): void
    {
        //
    }
}

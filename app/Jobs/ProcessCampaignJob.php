<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $campaignId
    ) {}

    public function handle(): void
    {
        $campaign = Campaign::with(['audio:id', 'group:id'])
            ->findOrFail($this->campaignId);

        // Prevent duplicate processing
        if ($campaign->processed_at !== null) {
            return;
        }

        $user = User::query()
            ->select(['id', 'balance'])
            ->findOrFail($campaign->user_id);

        $costPerCall = $campaign->audio
            ->calculateCostForUser($user);

        $contactsQuery = $campaign->group
            ->contacts()
            ->select([
                'id',
                'phone_number',
            ]);

        $totalContacts = (clone $contactsQuery)->count();

        if ($totalContacts === 0) {
            return;
        }

        $totalCost = bcmul(
            (string) $costPerCall,
            (string) $totalContacts,
            2
        );

        DB::transaction(function () use (
            $campaign,
            $user,
            $contactsQuery,
            $costPerCall,
            $totalCost
        ) {
            // Atomic balance deduction
            $affected = User::query()
                ->whereKey($user->id)
                ->where('balance', '>=', $totalCost)
                ->decrement('balance', $totalCost);

            if ($affected === 0) {
                throw new RuntimeException('Insufficient balance.');
            }

            $freshUser = User::query()
                ->select(['id', 'balance'])
                ->findOrFail($user->id);

            $now = now();

            $contactsQuery->chunkById(1000, function ($contacts) use (
                $campaign,
                $costPerCall,
                $now
            ) {
                $payload = [];

                foreach ($contacts as $contact) {
                    $payload[] = [
                        'user_id' => $campaign->user_id,
                        'campaign_id' => $campaign->id,
                        'caller_id' => $campaign->caller_id,
                        'audio_id' => $campaign->audio_id,
                        'contact_id' => $contact->id,
                        'phone_number' => $contact->phone_number,
                        'status' => CallStatus::Pending,
                        'type' => CallType::Marketing,
                        'scheduled_at' => $campaign->scheduled_at,
                        'cost' => $costPerCall,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                Call::insert($payload);
            });

            $campaign->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Debit,
                'amount' => $totalCost,
                'balance_before' => bcadd(
                    (string) $freshUser->balance,
                    (string) $totalCost,
                    2
                ),
                'balance_after' => $freshUser->balance,
                'currency' => 'BDT',
                'description' => "Campaign #{$campaign->id} call charges",
            ]);

            $campaign->forceFill([
                'processed_at' => now(),
            ])->saveQuietly();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\CampaignApproval;
use App\Enums\TransactionType;
use App\Filament\Admin\Resources\Calling\Campaigns\CampaignResource;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\Transaction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class CampaignObserver
{
    /**
     * Handle the Campaign "created" event.
     *
     * @throws Throwable
     */
    public function created(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            $campaign->loadMissing(['group.contacts', 'audio', 'user']);
            $user = User::query()
                ->whereKey($campaign->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Auto-approve if user has auto_approve_campaigns enabled
            if ($user->auto_approve_campaigns) {
                $campaign->updateQuietly(['approval' => CampaignApproval::Approved]);
            } else {
                $this->notifyAdmins($campaign);
            }

            $group = $campaign->group;
            if (! $group || $group->contacts->isEmpty()) {
                return;
            }

            $cost = $campaign->audio->calculateCostForUser($user);
            $now = now();
            $contacts = $group->contacts;

            // Calculate total cost upfront
            $totalCost = $cost * $contacts->count();

            // Check if user has sufficient balance
            if ($user->balance < $totalCost) {
                throw new RuntimeException('Insufficient balance');
            }

            // Bulk insert calls
            $callsData = $contacts->map(function ($contact) use ($campaign, $user, $cost, $now) {
                return [
                    'user_id' => $user->id,
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
            $insertedCalls = Call::select(['id', 'user_id', 'campaign_id'])
                ->where('campaign_id', $campaign->id)
                ->get(['id', 'user_id']);

            // Create transactions in bulk
            $transactionsData = $insertedCalls->map(function (Call $call) use ($user, $cost, $now) {
                return [
                    'user_id' => $user->id,
                    'type' => TransactionType::Debit,
                    'amount' => $cost,
                    'currency' => 'BDT',
                    'description' => "Call charge for call ID $call->id",
                    'transactionable_type' => Call::class,
                    'transactionable_id' => $call->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            // Insert transactions in chunks
            foreach (array_chunk($transactionsData, 1000) as $chunk) {
                Transaction::insert($chunk);
            }

            // Deduct total balance from user in a single update
            $user->decrement('balance', $totalCost);
        });
    }

    private function notifyAdmins(Campaign $campaign): void
    {
        $admins = Cache::remember(
            'users:admins',
            now()->addMinutes(15),
            fn () => User::admin()->get()
        );

        Notification::make()
            ->title('New Campaign Pending Approval')
            ->body("Campaign \"{$campaign->title}\" created by {$campaign->user->name} requires approval.")
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('Review Campaign')
                    ->url(CampaignResource::getUrl('view', ['record' => $campaign->id], panel: 'admin')),
            ])
            ->sendToDatabase($admins);
    }
}

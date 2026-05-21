<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CampaignApproval;
use App\Filament\Admin\Resources\Calling\Campaigns\CampaignResource;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class CampaignObserver
{
    public function created(Campaign $campaign): void
    {
        $user = User::query()
            ->select(['id', 'auto_approve_campaigns'])
            ->findOrFail($campaign->user_id);

        // Auto approval
        if ($user->auto_approve_campaigns) {
            $campaign->forceFill([
                'approval' => CampaignApproval::Approved,
            ])->saveQuietly();

            DB::afterCommit(function () use ($campaign) {
                ProcessCampaignJob::dispatch($campaign->id);
            });

            return;
        }

        DB::afterCommit(function () use ($campaign) {
            $this->notifyAdmins($campaign);
        });
    }

    private function notifyAdmins(Campaign $campaign): void
    {
        $admins = Cache::remember(
            'users:admins',
            now()->addMinutes(15),
            fn () => User::admin()->get()
        );

        foreach ($admins as $admin) {
            Notification::make()
                ->title('New Campaign Pending Approval')
                ->body(sprintf('Campaign [%s] requires approval.', str($campaign->title)->limit(30)))
                ->warning()
                ->actions([
                    Action::make('view')
                        ->label('Review Campaign')
                        ->url(CampaignResource::getUrl('view', ['record' => $campaign->id], panel: 'admin')),
                ]);
        }
    }
}

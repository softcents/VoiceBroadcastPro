<?php

namespace App\Observers;

use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaign;
use App\Models\Campaign;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CampaignObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Campaign "created" event.
     */
    public function created(Campaign $campaign): void
    {
        if(!$campaign->scheduled_at){
            $campaign->update([
                'status' => CampaignStatus::Processing
            ]);

            ProcessCampaign::dispatch($campaign->id);
        }
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

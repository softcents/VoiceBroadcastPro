<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3600;

    public function __construct(
        public int $campaignId
    ) {}

    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("Campaign not found: {$this->campaignId}");

            return;
        }

        // Only start if it's currently Pending or Failed (retry)
        if ($campaign->status !== CampaignStatus::Pending && $campaign->status !== CampaignStatus::Failed) {
            return;
        }

        $campaign->update([
            'status' => CampaignStatus::Processing,
            'started_at' => now(),
        ]);

        Log::info("Campaign #{$campaign->id} transitioned to Processing. Dispatcher cron will take over.");
    }

    public function failed(Throwable $exception): void
    {
        $campaign = Campaign::find($this->campaignId);

        if ($campaign) {
            $campaign->update(['status' => CampaignStatus::Failed]);
        }

        Log::error("ProcessCampaign failed for campaign {$this->campaignId}", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

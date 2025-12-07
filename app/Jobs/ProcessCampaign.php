<?php

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ProcessCampaign implements ShouldQueue
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

        if (!$campaign) {
            Log::warning("Campaign not found: {$this->campaignId}");
            return;
        }

        $campaign->update(['status' => CampaignStatus::Processing]);

        $campaign->calls()
            ->where('status', '!=', CallStatus::Initiated)
            ->chunkById(50, function ($calls) use ($campaign) {
                RateLimiter::attempt(
                    "campaign:{$this->campaignId}",
                    $maxAttempts = 1,
                    function () use ($calls, $campaign) {
                        DB::transaction(function () use ($calls, $campaign) {
                            $callIds = $calls->pluck('id');

                            DB::table('calls')
                                ->whereIn('id', $callIds)
                                ->update(['status' => CallStatus::Initiated]);

                            $jobs = $calls->map(fn($call) => new ProcessCall($call->id));

                            $batch = Bus::batch($jobs->toArray())
                                ->name("Campaign {$campaign->id} - Chunk")
                                ->allowFailures()
                                ->finally(function () use ($campaign) {
                                    // Check if all calls are processed
                                    $pendingCalls = $campaign->calls()
                                        ->where('status', CallStatus::Initiated)
                                        ->count();

                                    if ($pendingCalls === 0) {
                                        $campaign->update([
                                            'status' => CampaignStatus::Completed,
                                        ]);
                                    }
                                })
                                ->dispatch();

                            Log::info("Batch dispatched for campaign {$campaign->id}", [
                                'batch_id' => $batch->id,
                                'jobs_count' => $calls->count()
                            ]);
                        });
                    },
                    $decaySeconds = 10
                );
            });
    }

    public function failed(\Throwable $exception): void
    {
        $campaign = Campaign::find($this->campaignId);

        if ($campaign) {
            $campaign->update(['status' => CampaignStatus::Failed]);
        }

        Log::error("ProcessCampaign failed for campaign {$this->campaignId}", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

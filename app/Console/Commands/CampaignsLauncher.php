<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaign;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class CampaignsLauncher extends Command
{
    protected $signature = 'campaigns:launch
                            {--limit=10 : Maximum number of campaigns to process per chunk}
                            {--delay=5 : Delay in seconds between chunks}';

    protected $description = 'Launch scheduled campaigns to queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');

        $this->components->info('Processing scheduled campaigns');
        $this->newLine();

        $processedChunks = 0;
        $totalCampaigns = 0;

        try {
            Campaign::where('status', CampaignStatus::Pending)
                ->whereNotNull('scheduled_at')
                ->wherePast('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->chunkById($limit, function ($campaigns) use ($delay, &$processedChunks, &$totalCampaigns) {

                    RateLimiter::attempt(
                        'process-scheduled-campaigns',
                        $maxAttempts = 1,
                        function () use ($campaigns, &$processedChunks, &$totalCampaigns) {
                            foreach ($campaigns as $campaign) {
                                try {
                                    // Update campaign status
                                    $campaign->update([
                                        'status' => CampaignStatus::Processing,
                                    ]);

                                    // Dispatch the campaign processing job
                                    ProcessCampaign::dispatch($campaign->id);

                                    $this->components->task("Campaign #{$campaign->id}: {$campaign->name}", fn() => true);

                                    Log::info('Campaign queued for processing', [
                                        'campaign_id' => $campaign->id,
                                        'campaign_name' => $campaign->name,
                                    ]);

                                } catch (Throwable $e) {
                                    $this->components->error("Campaign #{$campaign->id} failed: {$e->getMessage()}");

                                    $campaign->update(['status' => CampaignStatus::Failed]);

                                    Log::error('Failed to queue campaign', [
                                        'campaign_id' => $campaign->id,
                                        'exception' => $e->getMessage(),
                                    ]);
                                }
                            }

                            $processedChunks++;
                            $totalCampaigns += $campaigns->count();

                            $this->newLine();
                            $this->components->twoColumnDetail("Chunk {$processedChunks}", "{$campaigns->count()} campaigns");
                        },
                        $decaySeconds = $delay
                    );
                });

            if ($totalCampaigns === 0) {
                $this->components->warn('No scheduled campaigns found');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->components->info('Processing complete');
            $this->components->twoColumnDetail('Total campaigns', (string) $totalCampaigns);
            $this->components->twoColumnDetail('Chunks', (string) $processedChunks);

            Log::info('Scheduled campaign processing completed', [
                'total_campaigns' => $totalCampaigns,
                'chunks' => $processedChunks,
            ]);

            return self::SUCCESS;

        } catch (Throwable $exception) {
            $this->components->error('Processing failed: '.$exception->getMessage());

            Log::error('Scheduled campaign processing failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}

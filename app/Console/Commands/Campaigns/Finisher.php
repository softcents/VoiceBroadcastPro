<?php

declare(strict_types=1);

namespace App\Console\Commands\Campaigns;

use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Console\Command;

final class Finisher extends Command
{
    protected $signature = 'campaigns:finish';

    protected $description = 'Mark campaigns as finished when all calls have been initiated';

    public function handle(): void
    {
        $count = 0;

        Campaign::where('status', CampaignStatus::Processing)
            ->whereDoesntHave('calls', function ($query) {
                $query->where('status', CallStatus::Pending);
            })
            ->chunk(100, function ($campaigns) use (&$count) {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => CampaignStatus::Finished]);
                    $count++;
                }
            });

        if ($count > 0) {
            $this->components->info("Finished {$count} campaign(s)");
        } else {
            $this->components->info('No campaigns to finish');
        }
    }
}

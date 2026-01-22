<?php

declare(strict_types=1);

namespace App\Console\Commands\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class Launcher extends Command
{
    protected $signature = 'campaigns:launch';

    protected $description = 'Launch campaigns';

    public function handle(): void
    {
        Campaign::pending()
            ->approved()
            ->where(function (Builder $query) {
                $query->whereNull('scheduled_at')
                    ->orWhere(function (Builder $q) {
                        $q->whereNotNull('scheduled_at')
                            ->where('scheduled_at', '<=', now());
                    });
            })
            ->chunk(100, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => CampaignStatus::Processing]);
                }
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class CampaignLauncher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:launch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launch campaigns';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Campaign::pending()
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

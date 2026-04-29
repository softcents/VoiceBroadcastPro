<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class CallsCleanup extends Command
{
    protected $signature = 'calls:cleanup';

    protected $description = 'Clean up calls stuck in incomplete statuses';

    public function handle(): void
    {
        $stuckQuery = Call::active()
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query->where('status', CallStatus::Initiated)
                            ->whereNotNull('initiated_at')
                            ->where('initiated_at', '<=', now()->subMinutes(5));
                    })
                    ->orWhere(function (Builder $query) {
                        $query->where('status', CallStatus::Ringing)
                            ->whereNotNull('ringing_at')
                            ->where('ringing_at', '<=', now()->subMinutes(5));
                    })
                    ->orWhere(function (Builder $query) {
                        $query->where('status', CallStatus::Answered)
                            ->whereNotNull('answered_at')
                            ->where('answered_at', '<=', now()->subHour());
                    });
            });

        $count = (clone $stuckQuery)->count();

        if ($count === 0) {
            $this->components->info('No stuck calls found');

            return;
        }

        $stuckQuery->update(['status' => CallStatus::Failed]);

        $this->components->task("Fixed {$count} stuck call".($count !== 1 ? 's' : ''), fn () => true);
        $this->components->twoColumnDetail('Updated calls', (string) $count);
    }
}

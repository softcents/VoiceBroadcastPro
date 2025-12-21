<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Models\Call;
use Illuminate\Console\Command;

final class CallsCleanup extends Command
{
    protected $signature = 'calls:cleanup';

    protected $description = 'Clean up calls stuck in incomplete statuses';

    public function handle(): void
    {
        $calls = Call::query()
            ->whereIn('status', [
                CallStatus::Initiated,
                CallStatus::Ringing,
                CallStatus::Answered,
            ])
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        $count = $calls->count();

        if ($count === 0) {
            $this->components->info('No stuck calls found');

            return;
        }

        foreach ($calls as $call) {
            $call->update(['status' => CallStatus::Failed]);
        }

        $this->components->task("Fixed {$count} stuck call".($count !== 1 ? 's' : ''), fn () => true);
        $this->components->twoColumnDetail('Updated calls', (string) $count);
    }
}

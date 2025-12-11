<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Models\Call;
use Illuminate\Console\Command;

final class FixStuckCall extends Command
{
    protected $signature = 'fix:stuck-calls';

    protected $description = 'Fix calls stuck in non-terminal statuses';

    public function handle(): void
    {
        $calls = Call::query()
            ->whereIn('status', [
                CallStatus::Initiated,
                CallStatus::Ringing,
                CallStatus::Answered,
            ])
            ->where('created_at', '<', now()->subHour())
            ->get();

        $count = $calls->count();

        foreach ($calls as $call) {
            $call->update(['status' => CallStatus::Failed]);
        }

        if ($count) {
            $this->info("Fixed {$count} stuck calls.");
        }
    }
}

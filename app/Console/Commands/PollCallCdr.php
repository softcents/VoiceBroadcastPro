<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Jobs\PollCallCdrJob;
use App\Models\Call;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:poll-call-cdr')]
#[Description('Command description')]
final class PollCallCdr extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        Call::query()
            ->whereStatus(CallStatus::Processing)
            ->whereNotNull('unique_id')
            ->where(function ($query) {
                $query->whereNull('next_poll_at')
                    ->orWherePast('next_poll_at');
            })
            ->orderBy('next_poll_at')
            ->limit(50)
            ->pluck('id')
            ->each(function (int $id): void {
                PollCallCdrJob::dispatch($id);
            });
    }
}

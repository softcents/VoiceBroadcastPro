<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Jobs\ProcessMarketingCallJob;
use App\Jobs\ProcessOtpCallJob;
use App\Models\Call;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:dispatch-call {--limit=100}')]
#[Description('Dispatch pending calls onto the queue, respecting server/caller concurrency limits via job middleware')]
final class DispatchCall extends Command
{
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $dispatched = 0;

        Call::query()
            ->withoutGlobalScopes()
            ->where('status', CallStatus::Pending)
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWherePast('scheduled_at');
            })
            ->whereNotNull('caller_id')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'type'])
            ->each(function (Call $call) use (&$dispatched): void {
                match ($call->type) {
                    CallType::Marketing => ProcessMarketingCallJob::dispatch($call->id),
                    CallType::OTP => ProcessOtpCallJob::dispatch($call->id),
                };

                $dispatched++;
            });

        $this->info("Dispatched {$dispatched} call(s).");

        return self::SUCCESS;
    }
}

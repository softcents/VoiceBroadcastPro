<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Jobs\ReconcileStaleCall;
use App\Models\Call;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reconcile-stale-calls {--threshold=5 : Minutes after which a processing call is considered stale}')]
#[Description('Find calls stuck in Processing status and reconcile them against Asterisk CDR')]
final class ReconcileStaleCalls extends Command
{
    public function handle(): void
    {
        $threshold = (int) $this->option('threshold');

        $calls = Call::query()
            ->withoutGlobalScopes()
            ->where('status', CallStatus::Processing)
            ->where('updated_at', '<', now()->subMinutes($threshold))
            ->get();

        $calls->each(fn (Call $call) => ReconcileStaleCall::dispatch($call->id));

        $this->info("Dispatched {$calls->count()} stale call(s) for reconciliation.");
    }
}

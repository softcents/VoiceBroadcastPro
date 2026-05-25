<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Jobs\InitiateCallJob;
use App\Models\Call;
use App\Models\Caller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;

#[Signature('app:dispatch-call')]
#[Description('Dispatch pending calls onto the queue, respecting server/caller concurrency limits via job middleware')]
final class DispatchCall extends Command
{
    #[NoReturn]
    public function handle(): void
    {
        $callers = Caller::query()
            ->with('server')
            ->get();

        $serverProcessingCounts = Call::query()
            ->select('callers.server_id', DB::raw('COUNT(*) as count'))
            ->join('callers', 'callers.id', '=', 'calls.caller_id')
            ->whereIn('calls.status', [CallStatus::Processing, CallStatus::Initiated])
            ->groupBy('callers.server_id')
            ->pluck('count', 'callers.server_id');

        $callerProcessingCounts = Call::query()
            ->select('caller_id', DB::raw('COUNT(*) as count'))
            ->whereIn('status', [CallStatus::Processing, CallStatus::Initiated])
            ->groupBy('caller_id')
            ->pluck('count', 'caller_id');

        $serverUsedSlots = [];

        /** @var Collection<int, Call> $results */
        $results = collect();

        foreach ($callers as $caller) {
            $callerProcessing = $callerProcessingCounts->get($caller->id, 0);
            $callerSlots = $caller->max_concurrency - $callerProcessing;
            if ($callerSlots <= 0) {
                continue;
            }

            $serverProcessing = $serverProcessingCounts->get($caller->server_id, 0);
            $serverUsed = $serverUsedSlots[$caller->server_id] ?? 0;
            $serverSlots =
                $caller->server->max_concurrency - $serverProcessing - $serverUsed;
            if ($serverSlots <= 0) {
                continue;
            }

            $slots = min($callerSlots, $serverSlots);

            $calls = Call::query()
                ->withoutGlobalScopes()
                ->select('id', 'type')
                ->where('caller_id', $caller->id)
                ->where('status', CallStatus::Pending)
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWherePast('scheduled_at');
                })
                ->orderBy('scheduled_at')
                ->orderBy('id')
                ->limit($slots)
                ->get();

            if ($calls->isEmpty()) {
                continue;
            }

            $serverUsedSlots[$caller->server_id] = $serverUsed + $calls->count();

            $results = $results->merge($calls);
        }

        if ($results->isNotEmpty()) {
            Call::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $results->pluck('id'))
                ->update([
                    'status' => CallStatus::Initiated,
                    'initiated_at' => now(),
                ]);
        }

        $results->each(fn ($call) => InitiateCallJob::dispatch($call->id)->onQueue('calling'));

        $this->info("Dispatched {$results->count()} calls onto the queue");
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands\Calls;

use App\Enums\CallStatus;
use App\Jobs\InitiateCallJob;
use App\Models\Call;
use App\Models\Caller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

#[Signature('calls:dispatch')]
#[Description('Dispatch pending calls onto the queue, respecting server/caller concurrency limits')]
final class DispatchCall extends Command
{
    /**
     * @throws Throwable
     */
    #[NoReturn]
    public function handle(): void
    {
        $dispatchedIds = DB::transaction(function (): array {
            $callers = Caller::query()
                ->with('server')
                ->get()
                ->keyBy('id');

            if ($callers->isEmpty()) {
                return [];
            }

            // ২. Processing/Initiated counts — server-wise ও caller-wise
            $serverProcessingCounts = Call::query()
                ->select('callers.server_id', DB::raw('COUNT(*) as count'))
                ->join('callers', 'callers.id', '=', 'calls.caller_id')
                ->whereIn('calls.status', [CallStatus::Processing, CallStatus::Initiated])
                ->groupBy('callers.server_id')
                ->pluck('count', 'callers.server_id')
                ->all();

            $callerProcessingCounts = Call::query()
                ->select('caller_id', DB::raw('COUNT(*) as count'))
                ->whereIn('status', [CallStatus::Processing, CallStatus::Initiated])
                ->groupBy('caller_id')
                ->pluck('count', 'caller_id')
                ->all();

            $pendingCalls = Call::query()
                ->withoutGlobalScopes()
                ->select('id', 'caller_id', 'scheduled_at')
                ->whereIn('caller_id', $callers->keys())
                ->where('status', CallStatus::Pending)
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWherePast('scheduled_at');
                })
                ->orderBy('scheduled_at')
                ->orderBy('id')
                ->lockForUpdate()  // race condition রোধ
                ->get()
                ->groupBy('caller_id');

            if ($pendingCalls->isEmpty()) {
                return [];
            }

            // ৪. Slot calculation ও call selection
            $serverUsedSlots = [];
            $selectedIds = [];

            foreach ($callers as $caller) {
                $callerProcessing = $callerProcessingCounts[$caller->id] ?? 0;
                $callerSlots = $caller->max_concurrency - $callerProcessing;

                if ($callerSlots <= 0) {
                    continue;
                }

                $serverProcessing = $serverProcessingCounts[$caller->server_id] ?? 0;
                $serverUsed = $serverUsedSlots[$caller->server_id] ?? 0;
                $serverSlots = $caller->server->max_concurrency - $serverProcessing - $serverUsed;

                if ($serverSlots <= 0) {
                    continue;
                }

                $slots = min($callerSlots, $serverSlots);
                $calls = $pendingCalls->get($caller->id, collect());

                if ($calls->isEmpty()) {
                    continue;
                }

                $picked = $calls->take($slots);

                $serverUsedSlots[$caller->server_id] = $serverUsed + $picked->count();

                foreach ($picked as $call) {
                    $selectedIds[] = $call->id;
                }
            }

            if (empty($selectedIds)) {
                return [];
            }

            // ৫. Atomic bulk update — transaction-এর ভেতরে
            Call::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $selectedIds)
                ->where('status', CallStatus::Pending) // double-check: শুধু Pending গুলোই update
                ->update([
                    'status' => CallStatus::Initiated,
                    'initiated_at' => now(),
                ]);

            return $selectedIds;
        });

        if (empty($dispatchedIds)) {
            $this->info('No calls to dispatch.');

            return;
        }

        // ৬. Transaction বাইরে job dispatch — DB commit হওয়ার পরে
        foreach ($dispatchedIds as $id) {
            InitiateCallJob::dispatch($id)->onQueue('calling');
        }

        $this->info('Dispatched '.count($dispatchedIds).' calls onto the queue.');
    }
}

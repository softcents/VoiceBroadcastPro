<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Enums\CallStatus;
use App\Models\Call;
use Closure;
use Illuminate\Support\Facades\Log;

final readonly class LimitCallerCalls
{
    public function __construct(private int $callId) {}

    public function handle(object $job, Closure $next): void
    {
        $call = Call::with('caller')->find($this->callId);

        if (! $call || ! $call->caller) {
            $next($job);

            return;
        }

        $caller = $call->caller;
        $limit = (int) $caller->max_concurrency;

        if ($limit <= 0) {
            $next($job);

            return;
        }

        $active = Call::query()
            ->withoutGlobalScopes()
            ->where('status', CallStatus::Processing)
            ->where('caller_id', $caller->id)
            ->count();

        if ($active >= $limit) {
            Log::info('Caller concurrency limit reached, releasing job', [
                'call_id' => $this->callId,
                'caller_id' => $caller->id,
                'active' => $active,
                'limit' => $limit,
            ]);

            $job->release(random_int(5, 15));

            return;
        }

        $next($job);
    }
}

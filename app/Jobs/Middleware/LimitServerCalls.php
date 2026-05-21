<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Enums\CallStatus;
use App\Models\Call;
use Closure;
use Illuminate\Support\Facades\Log;

final readonly class LimitServerCalls
{
    public function __construct(private int $callId) {}

    public function handle(object $job, Closure $next): void
    {
        $call = Call::with('caller.server')->find($this->callId);

        if (! $call || ! $call->caller?->server) {
            $next($job);

            return;
        }

        $server = $call->caller->server;
        $limit = (int) $server->max_concurrency;

        if ($limit <= 0) {
            $next($job);

            return;
        }

        $active = Call::query()
            ->withoutGlobalScopes()
            ->where('status', CallStatus::Processing)
            ->whereHas('caller', fn ($q) => $q->where('server_id', $server->id))
            ->count();

        if ($active >= $limit) {
            Log::info('Server concurrency limit reached, releasing job', [
                'call_id' => $this->callId,
                'server_id' => $server->id,
                'active' => $active,
                'limit' => $limit,
            ]);

            $job->release(random_int(5, 15));

            return;
        }

        $next($job);
    }
}

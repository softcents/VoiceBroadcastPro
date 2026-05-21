<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PullCallDeliveryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $callId) {}

    public function handle(): void
    {
        $call = Call::query()
            ->whereId($this->callId)
            ->whereNotNull('unique_id')
            ->withWhereHas('caller.server')
            ->first();

        if (! $call) {
            return; // Call not found or unique_id is null, nothing to do
        }
    }
}

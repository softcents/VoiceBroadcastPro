<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Jobs\ProcessCall;
use App\Models\Call;
use App\Settings\CallingSetting;

final class CallObserver
{
    /**
     * Handle the Call "created" event.
     */
    public function created(Call $call): void
    {
        if ($call->campaign_id === null && $call->scheduled_at === null) {
            ProcessCall::dispatch($call->id);
        }
    }

    /**
     * Handle the Call "updated" event.
     */
    public function updated(Call $call): void
    {
        if ($call->wasChanged('status') && $call->status === CallStatus::Completed) {
            $user = $call->user;

            if (! $user) {
                return;
            }

            $settings = app(CallingSetting::class);
            $durationInMinutes = ceil($call->duration / 60);

            // Rate is typically in dollars, balance is in cents (integer)
            // But checking User model, balance uses a cast to divide/multiply by 100.
            // So accessing $user->balance gives float (dollars).
            // We should just subtract the cost.

            $cost = $durationInMinutes * $settings->rate_per_minute;

            $user->decrement('balance', $cost); // Balance is float now
        }
    }

    /**
     * Handle the Call "deleted" event.
     */
    public function deleted(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "restored" event.
     */
    public function restored(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "force deleted" event.
     */
    public function forceDeleted(Call $call): void
    {
        //
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\TransactionType;
use App\Jobs\ProcessMarketingCall;
use App\Jobs\ProcessOtpCall;
use App\Models\Call;

final class CallObserver
{
    /**
     * Handle the Call "created" event.
     */
    public function created(Call $call): void
    {
        if ($call->campaign_id === null && $call->scheduled_at === null) {
            if ($call->type === CallType::OTP) {
                ProcessOtpCall::dispatch($call->id)
                    ->onQueue('otp');
            } else {
                ProcessMarketingCall::dispatch($call->id)
                    ->onQueue('marketing');
            }
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

            // Pulse Billing Logic
            $pulseDuration = $user->pulse_duration;
            $pulseRate = $user->pulse_rate;

            if ($pulseDuration > 0 && $call->duration > 0) {
                $pulses = ceil($call->duration / $pulseDuration);
                $cost = $pulses * $pulseRate;

                $user->decrement('balance', $cost);

                $call->cost = $cost;
                $call->saveQuietly();

                $user->transactions()->create([
                    'type' => TransactionType::Call,
                    'amount' => $cost,
                    'currency' => 'BDT',
                    'description' => "Charge for call ID {$call->id} ({$call->duration}s, {$pulses} pulses @ {$pulseRate}/pulse)",
                    'reference_type' => Call::class,
                    'reference_id' => $call->id,
                ]);
            }
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

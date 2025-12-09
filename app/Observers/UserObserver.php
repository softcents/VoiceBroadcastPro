<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\UserType;
use App\Models\User;
use App\Settings\CallingSetting;

final class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($user->type === UserType::User) {
            $settings = app(CallingSetting::class);

            $user->update([
                'pulse_rate' => $settings->pulse_rate,
                'pulse_duration' => $settings->pulse_duration,
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}

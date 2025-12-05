<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CallingSetting extends Settings
{
    public float $rate_per_minute;

    public static function group(): string
    {
        return 'calling';
    }
}

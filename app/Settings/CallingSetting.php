<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class CallingSetting extends Settings
{
    public float $rate_per_minute;

    public static function group(): string
    {
        return 'calling';
    }
}

<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use Illuminate\Support\Number;

function secondsToHuman(int|float $seconds): string
{
    return rescue(function () use ($seconds) {
        return CarbonInterval::seconds($seconds)->cascade()->forHumans();
    }, 'N/A');
}

function bytesToHuman(int $bytes): string
{
    return rescue(function () use ($bytes) {
        return Number::fileSize($bytes);
    }, 'N/A');
}

<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Storage;
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

function getFileUrl($path, $expires = 3600): string
{
    $storage = Storage::disk(config('filesystems.default'));

    if (! $storage->exists($path)) {
        return '';
    }

    if (config('filesystems.disks.public')) {
        return $storage->url($path);
    }

    return $storage->temporaryUrl($path, now()->addSeconds($expires));
}

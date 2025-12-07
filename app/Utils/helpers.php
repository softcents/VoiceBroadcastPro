<?php

declare(strict_types=1);

function secondsToHuman(int $seconds): string
{
    $days = intdiv($seconds, 86400);
    $seconds %= 86400;
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;
    $mins = intdiv($seconds, 60);
    $secs = $seconds % 60;

    $parts = [];
    if ($days) {
        $parts[] = "$days day".($days > 1 ? 's' : '');
    }
    if ($hours) {
        $parts[] = "$hours hour".($hours > 1 ? 's' : '');
    }
    if ($mins) {
        $parts[] = "$mins min".($mins > 1 ? 's' : '');
    }
    if ($secs || ! $parts) {
        $parts[] = "$secs sec".($secs > 1 ? 's' : '');
    }

    return implode(' ', $parts);
}

function bytesToHuman(int|float $bytes, int $precision = 2, bool $binary = false): string
{
    if ($bytes < 0) {
        $bytes = 0;
    }

    $base = $binary ? 1024 : 1000;
    $units = $binary
        ? ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB']
        : ['B', 'KB',  'MB',  'GB',  'TB',  'PB'];

    $i = 0;
    $value = (float) $bytes;

    while ($value >= $base && $i < count($units) - 1) {
        $value /= $base;
        $i++;
    }

    // No decimals for bytes
    $p = ($i === 0) ? 0 : $precision;

    // Trim trailing zeros like 1.50 -> 1.5, 1.00 -> 1
    $formatted = number_format($value, $p, '.', '');
    $formatted = mb_rtrim(mb_rtrim($formatted, '0'), '.');

    return $formatted.' '.$units[$i];
}

<?php

declare(strict_types=1);

use Carbon\CarbonInterval;

function secondsToHuman(int|float $seconds): string
{
    try {
        return CarbonInterval::seconds($seconds)->cascade()->forHumans();
    } catch (Exception $e) {
        return (string) $seconds;
    }
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

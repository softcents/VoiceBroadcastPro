<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('fix:stuck-calls')->everyMinute();
Schedule::command('schedule:process-campaigns')->everyMinute();
Schedule::command('schedule:process-calls')->everyMinute();

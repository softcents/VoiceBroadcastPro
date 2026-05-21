<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=24')->daily()->runInBackground();
Schedule::command('app:sync-trunk-status')->everyThirtySeconds()
    ->runInBackground()
    ->withoutOverlapping();
Schedule::command('app:poll-call-cdr')->everyThirtySeconds()
    ->runInBackground()
    ->withoutOverlapping();

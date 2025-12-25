<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('campaigns:launch')->everyThirtySeconds();
Schedule::command('campaigns:finish')->everyThirtySeconds();
Schedule::command('calls:cleanup')->everyThirtySeconds();
Schedule::command('calls:dispatch')->everyThirtySeconds();
Schedule::command('disposable:update')->daily();


<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('campaigns:launch')->everyThirtySeconds();
Schedule::command('campaigns:finish')->everyThirtySeconds();
Schedule::command('calls:reconcile')->everyThirtySeconds();
Schedule::command('calls:dispatch')->everyThirtySeconds();
Schedule::command('disposable:update')->daily();
Schedule::command('telescope:prune')->daily(); // This will remove 1 days log

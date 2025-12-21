<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('calls:cleanup')->everyMinute();
Schedule::command('campaigns:launch')->everyMinute();
Schedule::command('calls:dispatch')->everyMinute();

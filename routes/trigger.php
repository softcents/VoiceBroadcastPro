<?php

declare(strict_types=1);

use App\Support\Trigger\Listeners\CelEventListener;

/** @var App\Support\Trigger\Trigger $trigger */
$trigger->on('asteriskcdrdb.cel', 'write', [CelEventListener::class, 'handle']);

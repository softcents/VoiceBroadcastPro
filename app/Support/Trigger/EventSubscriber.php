<?php

declare(strict_types=1);

namespace App\Support\Trigger;

use MySQLReplication\Event\EventSubscribers;

class EventSubscriber extends EventSubscribers
{
    public function __construct(protected Trigger $trigger) {}
}

<?php

declare(strict_types=1);

namespace App\Support\Trigger\Subscribers;

use App\Support\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

final class Trigger extends EventSubscriber
{
    protected function allEvents(EventDTO $event): void
    {
        $this->trigger->dispatch($event);
    }
}

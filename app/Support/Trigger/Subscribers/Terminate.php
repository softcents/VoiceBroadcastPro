<?php

declare(strict_types=1);

namespace App\Support\Trigger\Subscribers;

use App\Support\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\EventDTO;

final class Terminate extends EventSubscriber
{
    protected function allEvents(EventDTO $event): void
    {
        if ($this->trigger->isReseted()) {
            $this->trigger->clearCurrent();
        }

        if ($this->trigger->isTerminated()) {
            exit('Terminated');
        }
    }
}

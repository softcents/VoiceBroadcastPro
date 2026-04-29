<?php

declare(strict_types=1);

namespace App\Support\Trigger\Subscribers;

use App\Support\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\HeartbeatDTO;

final class Heartbeat extends EventSubscriber
{
    public function onHeartbeat(HeartbeatDTO $event): void
    {
        $this->trigger->heartbeat($event);
    }
}

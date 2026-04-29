<?php

declare(strict_types=1);

namespace App\Support\Trigger\Listeners;

use App\Support\Trigger\EventSubscriber;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;

final class ListenerSubscriber extends EventSubscriber
{
    public function onUpdate(UpdateRowsDTO $event): void
    {
        ray($event)->label('onUpdate')->showApp();
    }

    public function onWrite(WriteRowsDTO $event): void
    {
        ray($event)->label('onWrite')->showApp();
    }
}

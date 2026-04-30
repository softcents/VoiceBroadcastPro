<?php

declare(strict_types=1);

namespace App\Support\Facades;

use Illuminate\Support\Facades\Facade;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\DTO\EventDTO;

/**
 * @see \App\Support\Trigger\Manager
 *
 * @method static \App\Support\Trigger\Trigger replication(int|\App\Models\Server|null $server = null)
 * @method static array replications()
 * @method static \App\Support\Trigger\Trigger|null current()
 *
 * @see \App\Support\Trigger\Trigger
 *
 * @method static Config configure(bool $keepUp)
 * @method static array getConfig()
 * @method static array getSubscribers()
 * @method static void loadRoutes()
 * @method static void start(bool $keepUp)
 * @method static void terminate()
 * @method static boolean isTerminated()
 * @method static void heartbeat(EventDTO $event)
 * @method static void rememberCurrent(BinLogCurrent $binLogCurrent)
 * @method static BinLogCurrent getCurrent()
 * @method static void clearCurrent()
 * @method static void on(string $table, $eventType, $action = null)
 * @method static void dispatch(EventDTO $event)
 * @method static void fire($events, EventDTO $event = null)
 * @method static array getEvents()
 */
final class Trigger extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'trigger.manager';
    }
}

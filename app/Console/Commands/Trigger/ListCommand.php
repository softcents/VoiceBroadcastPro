<?php

declare(strict_types=1);

namespace App\Console\Commands\Trigger;

use App\Support\Trigger\Trigger;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command as CommandAlias;

final class ListCommand extends Command
{
    protected $signature = 'trigger:list {--database= : Filter by database} {--table= : Filter by table} {--event= : Filter by event}';

    protected $description = 'List all trigger events registered in routes/trigger.php.';

    public function handle(): int
    {
        $config = config('trigger');

        $trigger = new Trigger('list', $config);
        $trigger->loadRoutes();

        if ($trigger->getConfig('detect')) {
            $trigger->detectDatabasesAndTables();
        }

        $actions = $trigger->getEvents();

        collect(Arr::dot($actions))
            ->transform(function ($action, $key) use ($actions) {
                [$database, $table, $event, $num, $action] = explode('.', $key.'.'.$this->transformActionToString($action));

                $key = sprintf('%s.%s.%s.%s', $database, $table, $event, $num);

                if (is_numeric($action)) {
                    $action = Arr::get($actions, $key);
                    $action = $this->transformActionToString($action);
                }

                return [
                    'key' => $key,
                    'database' => $database,
                    'table' => $table,
                    'event' => $event,
                    'num' => $num,
                    'action' => $action,
                ];
            })
            ->when($this->option('database'), fn ($collection, $database) => $collection->where('database', $database))
            ->when($this->option('table'), fn ($collection, $table) => $collection->where('table', $table))
            ->when($this->option('event'), fn ($collection, $event) => $collection->where('event', $event))
            ->unique('key')
            ->transform(fn ($item) => [
                $item['database'],
                $item['table'],
                $item['event'],
                $item['num'],
                $item['action'],
            ])
            ->tap(function ($items) {
                $this->table(['Database', 'Table', 'Event', 'Num', 'Action'], $items);
            });

        return CommandAlias::SUCCESS;
    }

    /**
     * Transform action to string.
     *
     * @param array|Closure|string $action
     * @return string
     */
    public function transformActionToString(array|Closure|string $action): string
    {
        if ($action instanceof Closure) {
            return Closure::class;
        }

        if (is_object($action)) {
            return $action::class;
        }

        if (is_array($action)) {
            [$class, $method] = $action + [1 => 'handle'];

            if (is_object($class)) {
                $class = $class::class;
            }

            return sprintf('%s@%s', $class, $method);
        }

        if (is_string($action) && ! str_contains($action, '@')) {
            return is_subclass_of($action, ShouldQueue::class)
                ? $action
                : "{$action}@handle";
        }

        return $action;
    }
}

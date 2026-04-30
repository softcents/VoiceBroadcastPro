<?php

declare(strict_types=1);

namespace App\Support\Trigger;

use App\Models\Server;
use InvalidArgumentException;

final class Manager
{
    /**
     * Replications keyed by server id.
     *
     * @var array<int, Trigger>
     */
    private array $replications = [];

    /**
     * The trigger currently being booted (used so route files can register
     * against the active replication via the Trigger facade).
     */
    private ?Trigger $current = null;

    public function __construct(private array $config = []) {}

    /**
     * Forward facade calls to the active or default replication.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return ($this->current ?? $this->replication())->{$method}(...$parameters);
    }

    /**
     * Get (and lazily build) the Trigger for a server.
     */
    public function replication(int|Server|null $server = null): Trigger
    {
        $server = $this->resolveServer($server);

        if (! isset($this->replications[$server->id])) {
            $config = array_merge($this->config, [
                'host' => $server->database_host,
                'port' => (int) ($server->database_port ?: 3306),
                'user' => $server->database_username,
                'password' => $server->database_password,
            ]);

            $trigger = new Trigger((string) $server->id, $config);

            // Expose the trigger so routes can use Trigger::on(...) (facade)
            // or $trigger->on(...) and have registrations land on this instance.
            $this->current = $trigger;

            try {
                $trigger->loadRoutes();
            } finally {
                $this->current = null;
            }

            if ($trigger->getConfig('detect')) {
                $trigger->detectDatabasesAndTables();
            }

            $this->replications[$server->id] = $trigger;
        }

        return $this->replications[$server->id];
    }

    /**
     * Get all built replications.
     *
     * @return array<int, Trigger>
     */
    public function replications(): array
    {
        return $this->replications;
    }

    /**
     * The replication currently being booted, if any.
     */
    public function current(): ?Trigger
    {
        return $this->current;
    }

    private function resolveServer(int|Server|null $server): Server
    {
        if ($server instanceof Server) {
            return $server;
        }

        if (is_int($server)) {
            $found = Server::query()->whereKey($server)->first();

            if (! $found) {
                throw new InvalidArgumentException("Server #{$server} not found", 1);
            }

            return $found;
        }

        $default = Server::query()
            ->where('enabled', true)
            ->whereNotNull('database_host')
            ->orderBy('id')
            ->first();

        if (! $default) {
            throw new InvalidArgumentException(
                'No enabled server with database connection info is available.',
                1
            );
        }

        return $default;
    }
}

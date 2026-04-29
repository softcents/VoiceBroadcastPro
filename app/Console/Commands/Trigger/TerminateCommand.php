<?php

declare(strict_types=1);

namespace App\Console\Commands\Trigger;

use App\Models\Server;
use App\Support\Facades\Trigger;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

final class TerminateCommand extends Command
{
    protected $signature = 'trigger:terminate {--server= : server id (omit to terminate all enabled)} {--reset : reset replication position}';

    protected $description = 'Terminate the replication process for a server so the supervisor can restart it.';

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');

        if ($serverId = $this->option('server')) {
            $server = Server::query()->whereKey((int) $serverId)->first();

            if (! $server) {
                $this->error("Server #{$serverId} not found.");

                return CommandAlias::FAILURE;
            }

            $this->terminate($server, $reset);

            return CommandAlias::SUCCESS;
        }

        $servers = Server::query()
            ->where('enabled', true)
            ->whereNotNull('database_host')
            ->get();

        if ($servers->isEmpty()) {
            $this->warn('No enabled servers with database connection info.');

            return CommandAlias::SUCCESS;
        }

        foreach ($servers as $server) {
            $this->terminate($server, $reset);
        }

        return CommandAlias::SUCCESS;
    }

    private function terminate(Server $server, bool $reset): void
    {
        $trigger = Trigger::replication($server);

        $trigger->terminate();
        $this->info("Broadcasting restart signal for server #{$server->id} ({$server->name}).");

        if ($reset) {
            $trigger->reset();
            $this->info("Replication position reset for server #{$server->id}.");
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands\Trigger;

use App\Models\Server;
use App\Support\Facades\Trigger;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

final class StatusCommand extends Command
{
    protected $signature = 'trigger:status {--server= : server id (omit to summarize all enabled servers)}';

    protected $description = 'Show binlog status of trigger replications.';

    public function handle(): int
    {
        if ($serverId = $this->option('server')) {
            return $this->showOne((int) $serverId);
        }

        return $this->showAll();
    }

    private function showOne(int $serverId): int
    {
        $server = Server::query()->whereKey($serverId)->first();

        if (! $server) {
            $this->error("Server #{$serverId} not found.");

            return CommandAlias::FAILURE;
        }

        $trigger = Trigger::replication($server);
        $binLogCurrent = $trigger->getCurrent();

        if (is_null($binLogCurrent)) {
            $this->warn("Binlog info for server #{$server->id} ({$server->name}) is empty.");

            return CommandAlias::SUCCESS;
        }

        $this->table(
            ['Name', 'Value'],
            [
                ['Server', "#{$server->id} {$server->name}"],
                ['ConnectionStatus', (string) ($server->connection_status ?? '-')],
                ['BinLogPosition', $binLogCurrent->getBinLogPosition()],
                ['BinFileName', $binLogCurrent->getBinFileName()],
            ]
        );

        return CommandAlias::SUCCESS;
    }

    private function showAll(): int
    {
        $servers = Server::query()
            ->where('enabled', true)
            ->whereNotNull('database_host')
            ->orderBy('id')
            ->get();

        if ($servers->isEmpty()) {
            $this->warn('No enabled servers with database connection info.');

            return CommandAlias::SUCCESS;
        }

        $rows = $servers->map(function (Server $server) {
            $current = Trigger::replication($server)->getCurrent();

            return [
                $server->id,
                $server->name,
                $server->connection_status ?? '-',
                $current?->getBinFileName() ?? '-',
                $current?->getBinLogPosition() ?? '-',
            ];
        })->all();

        $this->table(['ID', 'Server', 'Connection', 'BinFile', 'Position'], $rows);

        return CommandAlias::SUCCESS;
    }
}

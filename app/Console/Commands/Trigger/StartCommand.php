<?php

declare(strict_types=1);

namespace App\Console\Commands\Trigger;

use App\Models\Server;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class StartCommand extends Command
{
    protected $signature = 'trigger:start {--reset : reset replication position for spawned workers}';

    protected $description = 'Supervise per-server MySQL replication workers, reacting to changes in the servers table.';

    /**
     * Running workers keyed by server id.
     *
     * @var array<int, Process>
     */
    private array $processes = [];

    /**
     * Server fingerprint per id, used to detect config changes that require a restart.
     *
     * @var array<int, string>
     */
    private array $fingerprints = [];

    public function handle(): int
    {
        $this->components->info('Starting Trigger Supervisor');

        $this->writePidFile();

        $loop = Loop::get();

        if (! $loop) {
            $this->components->error('Failed to get event loop instance');

            return Command::FAILURE;
        }

        $loop->addSignal(SIGINT, fn () => $this->shutdown($loop));
        $loop->addSignal(SIGTERM, fn () => $this->shutdown($loop));
        $loop->addSignal(SIGUSR1, function () use ($loop) {
            $this->components->warn('Reload signal received');
            $this->reloadWorkers($loop);
        });

        $interval = max(1, (int) config('trigger.check_interval', 5));

        $loop->addPeriodicTimer((float) $interval, function () use ($loop) {
            $this->checkServers($loop);
            $this->drainWorkerOutput();
        });

        $this->checkServers($loop);

        $this->components->info('Event loop running');
        $this->line('<fg=gray>Press Ctrl+C to stop</>');
        $loop->run();

        return Command::SUCCESS;
    }

    private function checkServers(LoopInterface $loop): void
    {
        try {
            DB::connection()->reconnect();
        } catch (Exception $e) {
            $this->components->error('Database connection failed: '.$e->getMessage());

            return;
        }

        $servers = Server::query()
            ->where('enabled', true)
            ->whereNotNull('database_host')
            ->get();

        $activeIds = [];

        foreach ($servers as $server) {
            $activeIds[] = $server->id;
            $fingerprint = $this->fingerprintFor($server);

            if (! isset($this->processes[$server->id])) {
                $this->spawnWorker($server, $fingerprint);

                continue;
            }

            $process = $this->processes[$server->id];

            if (! $process->isRunning()) {
                $this->components->warn("Worker for server #{$server->id} ({$server->name}) exited; respawning");
                $this->stopWorker($server->id);
                $this->spawnWorker($server, $fingerprint);

                continue;
            }

            if (($this->fingerprints[$server->id] ?? null) !== $fingerprint) {
                $this->components->warn("Server #{$server->id} ({$server->name}) config changed; restarting worker");
                $this->stopWorker($server->id);
                $this->spawnWorker($server, $fingerprint);
            }
        }

        foreach (array_keys($this->processes) as $serverId) {
            if (! in_array($serverId, $activeIds, true)) {
                $this->components->task("Stopping worker for server #{$serverId}", fn () => true);
                $this->stopWorker($serverId);
            }
        }
    }

    private function spawnWorker(Server $server, string $fingerprint): void
    {
        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        $args = [
            $php,
            base_path('artisan'),
            'trigger:replicate',
            '--server='.$server->id,
        ];

        if ($this->option('reset')) {
            $args[] = '--reset';
        }

        if ($this->getOutput()->isVerbose()) {
            $args[] = '-v';
        }

        $process = new Process($args, base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->start();

        $this->processes[$server->id] = $process;
        $this->fingerprints[$server->id] = $fingerprint;

        $this->components->task("Spawned worker for server #{$server->id} ({$server->name}) pid={$process->getPid()}", fn () => true);
    }

    private function stopWorker(int $serverId): void
    {
        $process = $this->processes[$serverId] ?? null;

        if ($process instanceof Process && $process->isRunning()) {
            try {
                $process->stop(5, SIGTERM);
            } catch (Throwable $e) {
                $this->components->warn("Failed to stop worker #{$serverId}: ".$e->getMessage());
            }
        }

        unset($this->processes[$serverId], $this->fingerprints[$serverId]);
    }

    private function drainWorkerOutput(): void
    {
        foreach ($this->processes as $serverId => $process) {
            $stdout = $process->getIncrementalOutput();
            $stderr = $process->getIncrementalErrorOutput();

            if ($stdout !== '') {
                foreach (preg_split('/\r?\n/', mb_rtrim($stdout)) ?: [] as $line) {
                    if ($line !== '') {
                        $this->line("<fg=gray>[#{$serverId}]</> ".$line);
                    }
                }
            }

            if ($stderr !== '') {
                foreach (preg_split('/\r?\n/', mb_rtrim($stderr)) ?: [] as $line) {
                    if ($line !== '') {
                        $this->line("<fg=red>[#{$serverId}]</> ".$line);
                    }
                }
            }
        }
    }

    private function reloadWorkers(LoopInterface $loop): void
    {
        foreach (array_keys($this->processes) as $serverId) {
            $this->stopWorker($serverId);
        }

        $this->checkServers($loop);
        $this->components->info('Reload complete');
    }

    private function shutdown(LoopInterface $loop): void
    {
        $this->newLine();
        $this->components->warn('Shutting down');

        foreach (array_keys($this->processes) as $serverId) {
            $this->stopWorker($serverId);
        }

        $this->removePidFile();
        $loop->stop();
        $this->components->info('Shutdown complete');
    }

    private function fingerprintFor(Server $server): string
    {
        return hash('sha1', implode('|', [
            (string) $server->database_host,
            (string) ($server->database_port ?? ''),
            (string) ($server->database_username ?? ''),
            (string) ($server->database_password ?? ''),
            (string) ($server->updated_at?->timestamp ?? ''),
        ]));
    }

    private function getPidFilePath(): string
    {
        return storage_path('app/private/trigger.pid');
    }

    private function writePidFile(): void
    {
        $pid = getmypid();
        @file_put_contents($this->getPidFilePath(), (string) $pid);
        $this->components->twoColumnDetail('Process ID', (string) $pid);
    }

    private function removePidFile(): void
    {
        $pidFile = $this->getPidFilePath();
        if (file_exists($pidFile)) {
            @unlink($pidFile);
        }
    }
}

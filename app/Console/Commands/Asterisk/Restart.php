<?php

declare(strict_types=1);

namespace App\Console\Commands\Asterisk;

use Illuminate\Console\Command;

final class Restart extends Command
{
    protected $signature = 'asterisk:restart';

    protected $description = 'Restart the Asterisk ARI daemon (reload connections)';

    public function handle(): int
    {
        $pidFile = storage_path('app/private/asterisk-ari.pid');

        if (! file_exists($pidFile)) {
            $this->components->error('Daemon is not running');

            return 1;
        }

        $pid = (int) mb_trim(file_get_contents($pidFile));

        if (! $pid) {
            $this->components->error('Invalid PID in state file');

            return 1;
        }

        // Check if process is actually running
        if (! posix_kill($pid, 0)) {
            $this->components->warn("Process {$pid} not found");
            $this->components->task('Cleaning up stale PID file', fn () => unlink($pidFile));

            return 1;
        }

        $this->components->info('Restarting daemon');

        // Send SIGUSR1 signal to trigger reload (like Octane)
        if (posix_kill($pid, SIGUSR1)) {
            $this->components->task('Sending reload signal', fn () => true);
            $this->components->twoColumnDetail('Process ID', (string) $pid);
            $this->line('<fg=gray>Daemon will reconnect to all servers</>');

            return 0;
        }
        $this->components->error('Failed to send reload signal');

        return 1;

    }
}

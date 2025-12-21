<?php

declare(strict_types=1);

namespace App\Console\Commands\Asterisk;

use Illuminate\Console\Command;

final class Stop extends Command
{
    protected $signature = 'asterisk:stop';

    protected $description = 'Stop the Asterisk ARI daemon';

    public function handle(): int
    {
        $pidFile = storage_path('app/private/asterisk-ari.pid');

        if (!file_exists($pidFile)) {
            $this->components->info('Daemon is not running');
            return 0;
        }

        $pid = (int) trim(file_get_contents($pidFile));

        if (!$pid) {
            $this->components->error('Invalid PID in state file');
            unlink($pidFile);
            return 1;
        }

        // Check if process is actually running
        if (!posix_kill($pid, 0)) {
            $this->components->warn('Daemon not found');
            $this->components->task('Cleaning up stale PID file', fn() => unlink($pidFile));
            return 0;
        }

        $this->components->info('Stopping daemon');
        $this->components->twoColumnDetail('Process ID', (string) $pid);

        // Send SIGTERM for graceful shutdown
        if (posix_kill($pid, SIGTERM)) {
            $this->components->task('Sending shutdown signal', fn() => true);
            $this->line('<fg=gray>Waiting for graceful exit...</>');

            // Wait up to 5 seconds for graceful shutdown
            $waited = 0;
            while ($waited < 5 && posix_kill($pid, 0)) {
                usleep(500000); // 0.5 seconds
                $waited += 0.5;
            }

            // Check if it's still running
            if (posix_kill($pid, 0)) {
                $this->components->warn('Forcing shutdown');
                posix_kill($pid, SIGKILL);
                sleep(1);
            }

            // Verify it's stopped
            if (!posix_kill($pid, 0)) {
                $this->components->info('Daemon stopped successfully');
                // Clean up PID file if it still exists
                if (file_exists($pidFile)) {
                    unlink($pidFile);
                }
                return 0;
            } else {
                $this->components->error('Failed to stop daemon');
                return 1;
            }
        } else {
            $this->components->error('Failed to send stop signal');
            return 1;
        }
    }
}

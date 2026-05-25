<?php

declare(strict_types=1);

namespace App\Console\Commands\Asterisk;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('asterisk:status')]
#[Description('Check the status of the Asterisk ARI daemon')]
final class Status extends Command
{
    public function handle(): int
    {
        $pidFile = storage_path('app/private/asterisk-ari.pid');

        if (! file_exists($pidFile)) {
            $this->components->warn('Daemon is not running');
            $this->components->twoColumnDetail('State', '<fg=red>● stopped</>');

            return 1;
        }

        $pid = (int) mb_trim(file_get_contents($pidFile));

        if (! $pid) {
            $this->components->error('Invalid PID in state file');

            return 1;
        }

        // Check if process is actually running
        if (! posix_kill($pid, 0)) {
            $this->components->warn('Daemon not running (stale PID file)');
            $this->components->twoColumnDetail('State', '<fg=red>● stopped</>');
            $this->components->task('Cleaning up PID file', fn () => unlink($pidFile));

            return 1;
        }

        // Process is running
        $this->components->info('Daemon is running');
        $this->newLine();
        $this->components->twoColumnDetail('State', '<fg=green>● active</>');
        $this->components->twoColumnDetail('Process ID', (string) $pid);
        $this->components->twoColumnDetail('PID File', $pidFile);

        // Get process info
        $processInfo = shell_exec("ps -p {$pid} -o etime,rss,command | tail -n 1");
        if ($processInfo) {
            $parts = preg_split('/\s+/', mb_trim($processInfo), 3);
            if (count($parts) >= 3) {
                $this->components->twoColumnDetail('Uptime', $parts[0]);
                $this->components->twoColumnDetail('Memory', number_format((int) $parts[1]).' KB');
                $this->components->twoColumnDetail('Command', '<fg=gray>'.$parts[2].'</>');
            }
        }

        return 0;
    }
}

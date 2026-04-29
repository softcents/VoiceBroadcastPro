<?php

declare(strict_types=1);

namespace App\Console\Commands\Trigger;

use App\Support\Facades\Trigger;
use Illuminate\Console\Command;

final class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:status {--R|replication=default : replication}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install config and routes.';

    public function handle(): void
    {
        $replication = $this->option('replication');
        $trigger = Trigger::replication($replication);
        $binLogCurrent = $trigger->getCurrent();

        if (is_null($binLogCurrent)) {
            $this->warn('binlog info of '.$replication.' is empty.');

            return;
        }

        $this->table(
            ['Name', 'Value'],
            [
                ['BinLogPosition', $binLogCurrent->getBinLogPosition()],
                ['BinFileName', $binLogCurrent->getBinFileName()],
                // ['Gtid', $binLogCurrent->getGtid()],
                // ['MariaDbGtid', $binLogCurrent->getMariaDbGtid()],
            ]
        );
    }
}

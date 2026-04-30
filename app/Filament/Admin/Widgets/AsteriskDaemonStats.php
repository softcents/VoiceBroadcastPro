<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Server;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AsteriskDaemonStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = 2;

    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $pid = $this->readPid();
        $running = $pid !== null && posix_kill($pid, 0);
        $uptime = 'N/A';
        $memory = 'N/A';

        if ($running) {
            $processInfo = shell_exec("ps -p {$pid} -o etime,rss | tail -n 1");
            if ($processInfo) {
                $parts = preg_split('/\s+/', mb_trim($processInfo), 2);
                if (count($parts) >= 2) {
                    $uptime = $parts[0];
                    $memory = bytesToHuman($parts[1] * 1024); // RSS is in KB
                }
            }
        }

        $statusStat = Stat::make('Supervisor Status', $running ? 'Running' : 'Stopped')
            ->description($running ? "Process ID: {$pid}" : 'Not running')
            ->descriptionIcon($running ? 'heroicon-m-circle-stack' : 'heroicon-m-x-circle', IconPosition::Before)
            ->color($running ? 'success' : 'danger')
            ->chart($running ? [1, 2, 3, 2, 1, 3, 2] : []);

        if ($running) {
            $statusStat->extraAttributes([
                'class' => 'cursor-pointer',
            ]);
        }

        return [
            $statusStat,

            Stat::make('Uptime', $uptime)
                ->description('Time running')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color('info'),

            Stat::make('Memory Usage', $memory)
                ->description('RAM consumption')
                ->descriptionIcon('heroicon-m-cpu-chip', IconPosition::Before)
                ->color('warning'),

            Stat::make(
                'Replication Workers',
                Server::query()
                    ->where('enabled', true)
                    ->whereNotNull('database_host')
                    ->count()
            )
                ->description('Servers eligible for replication')
                ->descriptionIcon('heroicon-m-signal', IconPosition::Before)
                ->color('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $pid = $this->readPid();
        $running = $pid !== null && posix_kill($pid, 0);

        return [
            Action::make('start')
                ->label('Start Supervisor')
                ->color('success')
                ->icon('heroicon-o-play-circle')
                ->visible(! $running)
                ->action(function () {
                    shell_exec('cd '.base_path().' && nohup php artisan trigger:start > /dev/null 2>&1 &');

                    Notification::make()
                        ->success()
                        ->title('Supervisor Starting')
                        ->body('The trigger supervisor is starting...')
                        ->send();
                }),

            Action::make('reload')
                ->label('Reload')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible($running)
                ->requiresConfirmation()
                ->modalDescription('All replication workers will be respawned.')
                ->action(function () {
                    $pid = $this->readPid();

                    if ($pid !== null && posix_kill($pid, 0)) {
                        posix_kill($pid, SIGUSR1);
                    }

                    Notification::make()
                        ->success()
                        ->title('Supervisor Reloading')
                        ->body('Replication workers are being respawned.')
                        ->send();
                }),

            Action::make('stop')
                ->label('Stop')
                ->color('danger')
                ->icon('heroicon-o-stop-circle')
                ->visible($running)
                ->requiresConfirmation()
                ->modalDescription('All replication workers will be terminated.')
                ->action(function () {
                    $pid = $this->readPid();

                    if ($pid !== null && posix_kill($pid, 0)) {
                        posix_kill($pid, SIGTERM);
                    }

                    Notification::make()
                        ->success()
                        ->title('Supervisor Stopped')
                        ->send();
                }),
        ];
    }

    private function readPid(): ?int
    {
        $pidFile = storage_path('app/private/trigger.pid');

        if (! file_exists($pidFile)) {
            return null;
        }

        $pid = (int) mb_trim(file_get_contents($pidFile));

        return $pid > 0 ? $pid : null;
    }
}

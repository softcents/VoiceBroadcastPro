<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Server;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Number;

final class AsteriskDaemonStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int|array|null $columns = 2;
    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $pidFile = storage_path('app/private/asterisk-ari.pid');
        $running = false;
        $pid = null;
        $uptime = 'N/A';
        $memory = 'N/A';

        if (file_exists($pidFile)) {
            $pid = (int)mb_trim(file_get_contents($pidFile));

            if ($pid && posix_kill($pid, 0)) {
                $running = true;

                // Get process info
                $processInfo = shell_exec("ps -p {$pid} -o etime,rss | tail -n 1");
                if ($processInfo) {
                    $parts = preg_split('/\s+/', mb_trim($processInfo), 2);
                    if (count($parts) >= 2) {
                        $uptime = $parts[0];
                        $memory = Number::fileSize($parts[1] * 1024); // RSS is in KB
                    }
                }
            }
        }

        $statusStat = Stat::make('Daemon Status', $running ? 'Running' : 'Stopped')
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

            Stat::make('Active Connections', Server::where('connection_status', 'connected')->count())
                ->description('Connected servers')
                ->descriptionIcon('heroicon-m-signal', IconPosition::Before)
                ->color('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $pidFile = storage_path('app/private/asterisk-ari.pid');
        $running = false;

        if (file_exists($pidFile)) {
            $pid = (int)mb_trim(file_get_contents($pidFile));
            $running = $pid && posix_kill($pid, 0);
        }

        return [
            Action::make('start')
                ->label('Start Daemon')
                ->color('success')
                ->icon('heroicon-o-play-circle')
                ->visible(!$running)
                ->action(function () {
                    shell_exec('cd ' . base_path() . ' && nohup php artisan asterisk:start > /dev/null 2>&1 &');

                    Notification::make()
                        ->success()
                        ->title('Daemon Starting')
                        ->body('The Asterisk daemon is starting...')
                        ->send();
                }),

            Action::make('restart')
                ->label('Restart')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible($running)
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('asterisk:restart');

                    Notification::make()
                        ->success()
                        ->title('Daemon Restarting')
                        ->body('All server connections will be refreshed.')
                        ->send();
                }),

            Action::make('stop')
                ->label('Stop')
                ->color('danger')
                ->icon('heroicon-o-stop-circle')
                ->visible($running)
                ->requiresConfirmation()
                ->modalDescription('All active connections will be closed.')
                ->action(function () {
                    Artisan::call('asterisk:stop');

                    Notification::make()
                        ->success()
                        ->title('Daemon Stopped')
                        ->send();
                }),
        ];
    }
}

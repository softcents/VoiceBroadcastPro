<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Widgets;

use App\Enums\CallStatus;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

final class CampaignStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        if (! $this->record instanceof Campaign) {
            return [];
        }

        $calls = $this->record->calls();
        $totalCalls = $calls->count();
        $completedCalls = (clone $calls)->where('status', CallStatus::Completed)->count();
        $failedCalls = (clone $calls)->where('status', CallStatus::Failed)->count();
        $pendingCalls = (clone $calls)->where('status', CallStatus::Pending)->count();
        $answeredCalls = (clone $calls)->where('status', CallStatus::Answered)->count();
        $busyCalls = (clone $calls)->where('status', CallStatus::Busy)->count();
        $notAnsweredCalls = (clone $calls)->where('status', CallStatus::NotAnswered)->count();

        $answeredRate = $totalCalls > 0
            ? round((($completedCalls + $answeredCalls) / $totalCalls) * 100, 1)
            : 0;

        $totalCost = (float) (clone $calls)->sum('cost');
        $avgDuration = (float) ((clone $calls)->where('duration', '>', 0)->avg('duration') ?? 0);

        return [
            Stat::make('Total Calls', number_format($totalCalls))
                ->description('All calls in campaign')
                ->color('gray'),

            Stat::make('Completed', number_format($completedCalls))
                ->description('Successfully completed')
                ->color('success'),

            Stat::make('Failed', number_format($failedCalls))
                ->description('Could not connect')
                ->color('danger')
                ->action(
                    Action::make('retryFailed')
                        ->label('Retry Failed Calls')
                        ->url('https://example.com/retry-failed-calls') // Replace with actual URL or action
                ),

            Stat::make('Pending', number_format($pendingCalls))
                ->description('Waiting to be processed')
                ->color('warning'),

            Stat::make('Busy', number_format($busyCalls))
                ->description('Line was busy')
                ->color('info'),

            Stat::make('Not Answered', number_format($notAnsweredCalls))
                ->description('No response')
                ->color('gray'),

            Stat::make('Answer Rate', $answeredRate . '%')
                ->description('Completed / Total')
                ->color($answeredRate >= 50 ? 'success' : ($answeredRate >= 25 ? 'warning' : 'danger')),

            Stat::make('Total Cost', '৳' . number_format($totalCost, 2))
                ->description('Campaign expenditure')
                ->color('primary'),
        ];
    }
}

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

    protected ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        if (! $this->record instanceof Campaign) {
            return [];
        }

        $stats = $this->record->calls()
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing_calls,
                COALESCE(SUM(cost), 0) as total_cost
            ', [
                CallStatus::Completed->value,
                CallStatus::Failed->value,
                CallStatus::Pending->value,
                CallStatus::Processing->value,
            ])
            ->first();

        $totalCalls      = (int) $stats->total_calls;
        $completedCalls  = (int) $stats->completed_calls;
        $failedCalls     = (int) $stats->failed_calls;
        $pendingCalls    = (int) $stats->pending_calls;
        $processingCalls = (int) $stats->processing_calls;
        $totalCost       = (float) $stats->total_cost;

        $completedRate = $totalCalls > 0
            ? round(($completedCalls / $totalCalls) * 100, 1)
            : 0;

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
                        ->url('https://example.com/retry-failed-calls')
                ),

            Stat::make('Pending', number_format($pendingCalls))
                ->description('Waiting to be processed')
                ->color('warning'),

            Stat::make('Processing', number_format($processingCalls))
                ->description('Currently in progress')
                ->color('info'),

            Stat::make('Completion Rate', $completedRate.'%')
                ->description('Completed / Total')
                ->color($completedRate >= 50 ? 'success' : ($completedRate >= 25 ? 'warning' : 'danger')),

            Stat::make('Total Cost', '৳'.number_format($totalCost, 2))
                ->description('Campaign expenditure')
                ->color('primary'),
        ];
    }
}

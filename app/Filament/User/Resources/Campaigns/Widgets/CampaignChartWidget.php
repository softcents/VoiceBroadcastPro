<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Widgets;

use App\Enums\CallStatus;
use App\Models\Campaign;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

final class CampaignChartWidget extends ChartWidget
{
    public ?Model $record = null;

    protected ?string $heading = 'Call Status Distribution';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        if (! $this->record instanceof Campaign) {
            return [];
        }

        $data = $this->record->calls()
            ->selectRaw('count(*) as count, status')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Map status to colors
        $statusColors = [
            CallStatus::Pending->value => 'rgb(234, 179, 8)',      // yellow/warning
            CallStatus::Initiated->value => 'rgb(59, 130, 246)',   // blue/info
            CallStatus::Ringing->value => 'rgb(168, 85, 247)',     // purple
            CallStatus::Answered->value => 'rgb(34, 197, 94)',     // green/success
            CallStatus::Completed->value => 'rgb(16, 185, 129)',   // emerald
            CallStatus::Failed->value => 'rgb(239, 68, 68)',       // red/danger
            CallStatus::Busy->value => 'rgb(249, 115, 22)',        // orange
            CallStatus::NotAnswered->value => 'rgb(156, 163, 175)', // gray
        ];

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($data as $status => $count) {
            $statusEnum = CallStatus::tryFrom($status);
            $labels[] = $statusEnum?->getLabel() ?? $status;
            $values[] = $count;
            $colors[] = $statusColors[$status] ?? 'rgb(156, 163, 175)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Calls',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];
    }
}

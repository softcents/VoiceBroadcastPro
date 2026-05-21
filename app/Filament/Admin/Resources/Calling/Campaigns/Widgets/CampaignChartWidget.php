<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calling\Campaigns\Widgets;

use App\Models\Campaign;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

final class CampaignChartWidget extends ChartWidget
{
    public ?Model $record = null;

    protected ?string $heading = 'Call Status Distribution';

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

        return [
            'datasets' => [
                [
                    'label' => 'Calls',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}

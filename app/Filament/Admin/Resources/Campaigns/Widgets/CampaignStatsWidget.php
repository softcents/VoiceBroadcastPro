<?php

namespace App\Filament\Admin\Resources\Campaigns\Widgets;

use App\Models\Campaign;
use App\Enums\CallStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CampaignStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof Campaign) {
            return [];
        }

        return [
            Stat::make('Total Calls', $this->record->calls()->count()),
            Stat::make('Answered', $this->record->calls()->where('status', CallStatus::Answered)->count()),
            Stat::make('Failed', $this->record->calls()->where('status', CallStatus::Failed)->count()),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Widgets;

use App\Enums\CallStatus;
use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

final class CampaignStatsWidget extends StatsOverviewWidget
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

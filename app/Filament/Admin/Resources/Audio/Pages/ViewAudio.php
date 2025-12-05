<?php

namespace App\Filament\Admin\Resources\Audio\Pages;

use App\Filament\Admin\Resources\Audio\AudioResource;
use App\Filament\Admin\Resources\Campaigns\Widgets\CampaignChartWidget;
use App\Filament\Admin\Resources\Campaigns\Widgets\CampaignStatsWidget;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAudio extends ViewRecord
{
    protected static string $resource = AudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignChartWidget::make(),
            CampaignStatsWidget::make(),
        ];
    }
}

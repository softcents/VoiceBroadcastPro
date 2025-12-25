<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Pages;

use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Filament\User\Resources\Campaigns\CampaignResource;
use App\Filament\User\Resources\Campaigns\Widgets\CampaignChartWidget;
use App\Filament\User\Resources\Campaigns\Widgets\CampaignStatsWidget;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use LaraZeus\Tabler\Tabler;

final class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryFailed')
                ->icon(Tabler::RefreshDot)
                ->label('Retry Failed')
                ->color('warning')
                ->outlined()
                ->requiresConfirmation()
                ->visible(fn(Campaign $record): bool => $record->calls()->retryable()->exists())
                ->action(function (Campaign $record): void {
                    DB::transaction(function () use ($record): void {
                        $record->calls()->retryable()->update([
                            'status' => CallStatus::Pending,
                        ]);

                        $record->update([
                            'status' => CampaignStatus::Pending,
                        ]);

                        Notification::make('retry_initiated')
                            ->title('Retry Initiated')
                            ->body('All failed calls are set to retry.')
                            ->success()
                            ->send();
                    });

                    $this->refresh();
                }),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CampaignChartWidget::class,
        ];
    }
}

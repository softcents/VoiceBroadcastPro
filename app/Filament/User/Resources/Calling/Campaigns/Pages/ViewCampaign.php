<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calling\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\User\Resources\Calling\Campaigns\CampaignResource;
use App\Filament\User\Resources\Calling\Campaigns\Widgets\CampaignChartWidget;
use App\Filament\User\Resources\Calling\Campaigns\Widgets\CampaignStatsWidget;
use App\Models\Call;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\Tabler\Tabler;
use Throwable;

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
                ->visible(fn (Campaign $record): bool => $record->calls()->retryable()->exists())
                ->action(function (Campaign $record): void {
                    $skipped = 0;

                    $record->calls()->retryable()->each(function (Call $call) use (&$skipped): void {
                        try {
                            $call->retry();
                        } catch (Throwable) {
                            $skipped++;
                        }
                    });

                    $hasPending = $record->calls()->pending()->exists();

                    if ($hasPending) {
                        $record->update(['status' => CampaignStatus::Pending]);
                    }

                    $body = $skipped > 0
                        ? "{$skipped} call(s) were skipped due to insufficient balance or retry limit."
                        : 'All eligible failed calls have been queued for retry.';

                    Notification::make('retry_initiated')
                        ->title('Retry Initiated')
                        ->body($body)
                        ->success()
                        ->send();

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

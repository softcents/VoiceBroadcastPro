<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Pages;

use App\Actions\Campaign\CreateCampaignAction;
use App\Filament\User\Resources\Campaigns\CampaignResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateCampaign extends CreateRecord
{
    protected const int BATCH_SIZE = 500; // Insert calls in batches for better performance

    protected const int MAX_CALLS_PER_CAMPAIGN = 10000; // Prevent memory issues

    protected static string $resource = CampaignResource::class;

    protected ?Collection $preparedCalls = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // We can optionally validate here using the Action's prepare method if we want to show errors before submission?
        // Or just let handleRecordCreation handle it.
        // The original code did pre-validation.

        try {
            app(CreateCampaignAction::class)->prepareCallsForCampaign(auth()->user(), $data);
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Error preparing campaign calls')
                ->body($e->getMessage())
                ->send();

            throw ValidationException::withMessages([
                'source' => 'Failed to prepare calls for the campaign. Please check your input.',
            ]);
        }

        return $data;
    }

    /**
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateCampaignAction::class)->execute(auth()->user(), $data);
    }
}

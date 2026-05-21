<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calling\Calls\Pages;

use App\Actions\CreateNewCall;
use App\Filament\User\Resources\Calling\Calls\CallResource;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @throws Exception
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateNewCall::class)->handle(auth()->user(), $data);
        } catch (Exception $e) {
            Notification::make()
                ->title('Call Creation Failed')
                ->body('Something went wrong while creating the call. Please try again.')
                ->danger()
                ->send();

            throw $e;
        }
    }
}

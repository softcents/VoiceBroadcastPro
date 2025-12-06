<?php

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPhonebook extends EditRecord
{
    protected static string $resource = PhonebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Phonebooks\Pages;

use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPhonebook extends ViewRecord
{
    protected static string $resource = PhonebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

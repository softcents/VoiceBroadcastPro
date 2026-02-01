<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Contacts\ContactResource;
use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use App\Models\Phonebook;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPhonebook extends ViewRecord
{
    protected static string $resource = PhonebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
//            Action::make('create_contact')
//                ->label('Add Contact')
//                ->url(fn(Phonebook $record) => ContactResource::getUrl('create', ['phonebook_id' => $record->id])),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Phonebooks\Pages;

use App\Filament\Admin\Resources\Contacts\ContactResource;
use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
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
            Action::make('create_contact')
                ->label('Add Contact')
                ->url(fn () => ContactResource::getUrl('create')),
        ];
    }
}

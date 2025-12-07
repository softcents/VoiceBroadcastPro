<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPhonebooks extends ListRecords
{
    protected static string $resource = PhonebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

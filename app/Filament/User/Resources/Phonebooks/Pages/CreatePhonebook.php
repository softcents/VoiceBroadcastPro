<?php

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhonebook extends CreateRecord
{
    protected static string $resource = PhonebookResource::class;
}

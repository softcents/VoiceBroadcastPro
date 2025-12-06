<?php

namespace App\Filament\User\Resources\Contacts\Pages;

use App\Filament\User\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}

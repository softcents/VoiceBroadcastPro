<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Contacts\Pages;

use App\Filament\User\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}

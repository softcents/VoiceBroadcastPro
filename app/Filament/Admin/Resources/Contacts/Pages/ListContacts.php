<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Contacts\Pages;

use App\Filament\Admin\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\ListRecords;

final class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;
}

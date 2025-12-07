<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Phonebooks\Pages;

use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePhonebook extends CreateRecord
{
    protected static string $resource = PhonebookResource::class;
}

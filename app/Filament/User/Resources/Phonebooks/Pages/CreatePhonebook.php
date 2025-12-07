<?php

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePhonebook extends CreateRecord
{
    protected static string $resource = PhonebookResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->phonebooks()->create($data);
    }
}

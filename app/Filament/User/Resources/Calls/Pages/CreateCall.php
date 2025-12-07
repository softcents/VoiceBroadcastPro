<?php

namespace App\Filament\User\Resources\Calls\Pages;

use App\Filament\User\Resources\Calls\CallResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->calls()->create($data);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Pages;

use App\Filament\User\Resources\Calls\CallResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function handleRecordCreation(array $data): Model
    {
        $data['user_id'] = auth()->id();

        return parent::handleRecordCreation($data);
    }
}

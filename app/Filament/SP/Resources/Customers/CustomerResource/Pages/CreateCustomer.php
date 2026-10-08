<?php

declare(strict_types=1);

namespace App\Filament\SP\Resources\Customers\CustomerResource\Pages;

use App\Enums\UserType;
use App\Filament\SP\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = UserType::User;
        $data['sp_id'] = auth()->id();

        return $data;
    }
}

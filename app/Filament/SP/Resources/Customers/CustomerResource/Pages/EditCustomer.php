<?php

declare(strict_types=1);

namespace App\Filament\SP\Resources\Customers\CustomerResource\Pages;

use App\Enums\UserType;
use App\Filament\SP\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;

final class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = UserType::User;
        $data['sp_id'] = auth()->id();

        return $data;
    }
}

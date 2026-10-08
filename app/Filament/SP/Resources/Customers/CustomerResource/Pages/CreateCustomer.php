<?php

declare(strict_types=1);

namespace App\Filament\SP\Resources\Customers\CustomerResource\Pages;

use App\Filament\SP\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}

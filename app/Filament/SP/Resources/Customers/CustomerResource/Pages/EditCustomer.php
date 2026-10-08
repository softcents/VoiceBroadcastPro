<?php

declare(strict_types=1);

namespace App\Filament\SP\Resources\Customers\CustomerResource\Pages;

use App\Filament\SP\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;

final class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}

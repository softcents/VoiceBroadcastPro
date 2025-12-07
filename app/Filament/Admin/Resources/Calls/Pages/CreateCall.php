<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calls\Pages;

use App\Filament\Admin\Resources\Calls\CallResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;
}

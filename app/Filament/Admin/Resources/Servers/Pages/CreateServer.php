<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Servers\Pages;

use App\Filament\Admin\Resources\Servers\ServerResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;
}

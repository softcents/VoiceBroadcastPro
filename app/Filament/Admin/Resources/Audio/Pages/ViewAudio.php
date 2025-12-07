<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Audio\Pages;

use App\Filament\Admin\Resources\Audio\AudioResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAudio extends ViewRecord
{
    protected static string $resource = AudioResource::class;
}

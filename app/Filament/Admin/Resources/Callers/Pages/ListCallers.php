<?php

namespace App\Filament\Admin\Resources\Callers\Pages;

use App\Filament\Admin\Resources\Callers\CallerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCallers extends ListRecords
{
    protected static string $resource = CallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

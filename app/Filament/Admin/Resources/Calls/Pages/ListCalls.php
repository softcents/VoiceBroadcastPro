<?php

namespace App\Filament\Admin\Resources\Calls\Pages;

use App\Filament\Admin\Resources\Calls\CallResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

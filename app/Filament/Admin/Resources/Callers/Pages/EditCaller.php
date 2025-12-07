<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Callers\Pages;

use App\Filament\Admin\Resources\Callers\CallerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCaller extends EditRecord
{
    protected static string $resource = CallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

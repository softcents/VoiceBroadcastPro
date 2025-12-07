<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Pages;

use App\Filament\User\Resources\Calls\CallResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCall extends EditRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

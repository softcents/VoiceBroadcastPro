<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Audience\Groups\Pages;

use App\Filament\User\Resources\Audience\Groups\GroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

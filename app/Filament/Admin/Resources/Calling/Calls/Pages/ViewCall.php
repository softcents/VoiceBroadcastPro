<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calling\Calls\Pages;

use App\Filament\Admin\Resources\Calling\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\Tabler\Tabler;

final class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry')
                ->label('Retry')
                ->icon(Tabler::RepeatOnce)
                ->color('danger')
                ->visible(fn (Call $record) => $record->can_retry)
                ->requiresConfirmation()
                ->action(fn (Call $record) => $record->retry()),
        ];
    }
}

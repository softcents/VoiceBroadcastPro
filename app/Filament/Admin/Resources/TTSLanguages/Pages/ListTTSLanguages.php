<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TTSLanguages\Pages;

use App\Filament\Admin\Resources\TTSLanguages\TTSLanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTTSLanguages extends ListRecords
{
    protected static string $resource = TTSLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

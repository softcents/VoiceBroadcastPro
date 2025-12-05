<?php

namespace App\Filament\Admin\Resources\TTSLanguages\Pages;

use App\Filament\Admin\Resources\TTSLanguages\TTSLanguageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTTSLanguage extends EditRecord
{
    protected static string $resource = TTSLanguageResource::class;


    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

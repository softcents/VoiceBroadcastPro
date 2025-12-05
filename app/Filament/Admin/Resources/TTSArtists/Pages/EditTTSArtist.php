<?php

namespace App\Filament\Admin\Resources\TTSArtists\Pages;

use App\Filament\Admin\Resources\TTSArtists\TTSArtistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTTSArtist extends EditRecord
{
    protected static string $resource = TTSArtistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

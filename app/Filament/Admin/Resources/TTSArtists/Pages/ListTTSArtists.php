<?php

namespace App\Filament\Admin\Resources\TTSArtists\Pages;

use App\Filament\Admin\Resources\TTSArtists\TTSArtistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTTSArtists extends ListRecords
{
    protected static string $resource = TTSArtistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

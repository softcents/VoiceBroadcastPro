<?php

namespace App\Filament\Admin\Resources\Audio\Schemas;

use App\Models\Audio;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AudioInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('approval')
                    ->badge(),
                TextEntry::make('message')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('language')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('gender')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('artist')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('original_path')
                    ->placeholder('-'),
                TextEntry::make('converted_path')
                    ->placeholder('-'),
                TextEntry::make('duration')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('size')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('mime_type')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Audio $record): bool => $record->trashed()),
            ]);
    }
}

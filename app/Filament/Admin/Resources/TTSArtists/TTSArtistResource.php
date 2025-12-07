<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TTSArtists;

use App\Filament\Admin\Resources\TTSArtists\Pages\CreateTTSArtist;
use App\Filament\Admin\Resources\TTSArtists\Pages\EditTTSArtist;
use App\Filament\Admin\Resources\TTSArtists\Pages\ListTTSArtists;
use App\Filament\Admin\Resources\TTSArtists\Schemas\TTSArtistForm;
use App\Filament\Admin\Resources\TTSArtists\Tables\TTSArtistsTable;
use App\Models\TTSArtist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class TTSArtistResource extends Resource
{
    protected static ?string $model = TTSArtist::class;

    protected static string|UnitEnum|null $navigationGroup = 'Text to Speech';

    protected static ?string $navigationLabel = 'Artists';

    protected static ?string $slug = 'tts-artists';

    public static function form(Schema $schema): Schema
    {
        return TTSArtistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TTSArtistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTTSArtists::route('/'),
            'create' => CreateTTSArtist::route('/create'),
            'edit' => EditTTSArtist::route('/{record}/edit'),
        ];
    }
}

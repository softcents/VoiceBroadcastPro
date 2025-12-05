<?php

namespace App\Filament\Admin\Resources\Audio;

use App\Filament\Admin\Resources\Audio\Pages\CreateAudio;
use App\Filament\Admin\Resources\Audio\Pages\EditAudio;
use App\Filament\Admin\Resources\Audio\Pages\ListAudio;
use App\Filament\Admin\Resources\Audio\Pages\ViewAudio;
use App\Filament\Admin\Resources\Audio\Schemas\AudioForm;
use App\Filament\Admin\Resources\Audio\Schemas\AudioInfolist;
use App\Filament\Admin\Resources\Audio\Tables\AudioTable;
use App\Models\Audio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AudioResource extends Resource
{
    protected static ?string $model = Audio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return AudioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AudioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AudioTable::configure($table);
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
            'index' => ListAudio::route('/'),
            'create' => CreateAudio::route('/create'),
            'view' => ViewAudio::route('/{record}'),
            'edit' => EditAudio::route('/{record}/edit'),
        ];
    }
}

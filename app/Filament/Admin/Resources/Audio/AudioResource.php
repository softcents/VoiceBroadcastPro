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
use App\Models\Scopes\OwnedByAuthUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Tabler\Tabler;

class AudioResource extends Resource
{
    protected static ?string $model = Audio::class;

    protected static string|BackedEnum|null $navigationIcon = Tabler::Music;

    protected static ?string $recordTitleAttribute = 'title';

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
            'view' => ViewAudio::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                OwnedByAuthUser::class,
            ]);
    }
}

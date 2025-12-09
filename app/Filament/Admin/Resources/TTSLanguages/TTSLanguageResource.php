<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TTSLanguages;

use App\Filament\Admin\Resources\TTSLanguages\Pages\CreateTTSLanguage;
use App\Filament\Admin\Resources\TTSLanguages\Pages\EditTTSLanguage;
use App\Filament\Admin\Resources\TTSLanguages\Pages\ListTTSLanguages;
use App\Filament\Admin\Resources\TTSLanguages\Pages\ViewTTSLanguage;
use App\Filament\Admin\Resources\TTSLanguages\Schemas\TTSLanguageForm;
use App\Filament\Admin\Resources\TTSLanguages\Schemas\TTSLanguageInfolist;
use App\Filament\Admin\Resources\TTSLanguages\Tables\TTSLanguagesTable;
use App\Models\TTSLanguage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class TTSLanguageResource extends Resource
{
    protected static ?string $model = TTSLanguage::class;

    protected static string|UnitEnum|null $navigationGroup = 'Text to Speech';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'Languages';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'tts-languages';

    public static function form(Schema $schema): Schema
    {
        return TTSLanguageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TTSLanguageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TTSLanguagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ArtistsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTTSLanguages::route('/'),
            'create' => CreateTTSLanguage::route('/create'),
            'view' => ViewTTSLanguage::route('/{record}'),
            'edit' => EditTTSLanguage::route('/{record}/edit'),
        ];
    }
}

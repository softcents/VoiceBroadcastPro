<?php

namespace App\Filament\Admin\Resources\Phonebooks;

use App\Filament\Admin\Resources\Phonebooks\Pages\CreatePhonebook;
use App\Filament\Admin\Resources\Phonebooks\Pages\EditPhonebook;
use App\Filament\Admin\Resources\Phonebooks\Pages\ListPhonebooks;
use App\Filament\Admin\Resources\Phonebooks\Pages\ViewPhonebook;
use App\Filament\Admin\Resources\Phonebooks\Schemas\PhonebookForm;
use App\Filament\Admin\Resources\Phonebooks\Schemas\PhonebookInfolist;
use App\Filament\Admin\Resources\Phonebooks\Tables\PhonebooksTable;
use App\Models\Phonebook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhonebookResource extends Resource
{
    protected static ?string $model = Phonebook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PhonebookForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PhonebookInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhonebooksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\Phonebooks\RelationManagers\ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhonebooks::route('/'),
            'create' => CreatePhonebook::route('/create'),
            'view' => ViewPhonebook::route('/{record}'),
            'edit' => EditPhonebook::route('/{record}/edit'),
        ];
    }
}

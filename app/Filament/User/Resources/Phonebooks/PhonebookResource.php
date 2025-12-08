<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks;

use App\Filament\User\Resources\Phonebooks\Pages\CreatePhonebook;
use App\Filament\User\Resources\Phonebooks\Pages\EditPhonebook;
use App\Filament\User\Resources\Phonebooks\Pages\ListPhonebooks;
use App\Filament\User\Resources\Phonebooks\Pages\ViewPhonebook;
use App\Filament\User\Resources\Phonebooks\RelationManagers\ContactsRelationManager;
use App\Filament\User\Resources\Phonebooks\Schemas\PhonebookForm;
use App\Filament\User\Resources\Phonebooks\Schemas\PhonebookInfolist;
use App\Filament\User\Resources\Phonebooks\Tables\PhonebooksTable;
use App\Models\Phonebook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PhonebookResource extends Resource
{
    protected static ?string $model = Phonebook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    protected static ?int $navigationSort = 20;

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
            ContactsRelationManager::class,
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

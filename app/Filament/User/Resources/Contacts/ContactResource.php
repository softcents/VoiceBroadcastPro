<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Contacts;

use App\Filament\User\Resources\Contacts\Pages\CreateContact;
use App\Filament\User\Resources\Contacts\Pages\EditContact;
use App\Filament\User\Resources\Contacts\Pages\ListContacts;
use App\Filament\User\Resources\Contacts\Pages\ViewContact;
use App\Filament\User\Resources\Contacts\Schemas\ContactForm;
use App\Filament\User\Resources\Contacts\Schemas\ContactInfolist;
use App\Filament\User\Resources\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Tabler\Tabler;

final class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Tabler::AddressBook;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
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
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('phonebook', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
    }
}

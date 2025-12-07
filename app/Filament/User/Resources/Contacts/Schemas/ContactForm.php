<?php

namespace App\Filament\User\Resources\Contacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        Select::make('phonebook_id')
                            ->relationship('phonebook', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(request()->integer('phonebook_id') ?? null),
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Last Name'),
                        PhoneInput::make('phone_number')
                            ->label('Phone Number')
                            ->defaultCountry('BD')
                            ->onlyCountries(['BD'])
                            ->enableIpLookup(false)
                            ->required()
                            ->rules(['phone:BD']),
                    ])
            ]);
    }
}

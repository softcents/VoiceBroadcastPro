<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->onlyCountries(['BD'])
                            ->defaultCountry('BD'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->prefixAction(Action::make('generate_password')
                                ->label('Generate')
                                ->iconButton()
                                ->icon(Heroicon::ArrowPath)
                                ->action(function (TextInput $component) {
                                    $component->state(bin2hex(random_bytes(4)));
                                })
                            ),
                    ])
            ]);
    }
}

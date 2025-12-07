<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        TextInput::make('ari_domain')
                            ->label('ARI Domain')
                            ->required(),
                        TextInput::make('ari_username')
                            ->label('ARI Username')
                            ->required(),
                        TextInput::make('ari_password')
                            ->label('ARI Password')
                            ->password()
                            ->required(),
                        TextInput::make('database_host')
                            ->label('DB Host')
                            ->required(),
                        TextInput::make('database_port')
                            ->label('DB Port')
                            ->required()
                            ->numeric()
                            ->default(3306),
                        TextInput::make('database_name')
                            ->label('DB Name')
                            ->required()
                            ->default('asteriskcdrdb'),
                        TextInput::make('database_username')
                            ->label('DB Username')
                            ->required(),
                        TextInput::make('database_password')
                            ->label('DB Password')
                            ->password()
                            ->required(),
                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->required(),
                    ]),
            ]);
    }
}

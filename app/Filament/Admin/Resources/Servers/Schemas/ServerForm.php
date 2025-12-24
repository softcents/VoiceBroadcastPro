<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;

final class ServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('General Information')
                    ->description('Basic server details and status.')
                    ->icon(Tabler::InfoCircle)
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('e.g. Primary Asterisk Server')
                            ->required()
                            ->prefixIcon(Tabler::Server),

                        Select::make('enabled')
                            ->label('Status')
                            ->boolean()
                            ->default(true)
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->prefixIcon(Tabler::Activity),
                    ]),

                Section::make('ARI Connection')
                    ->description('Configuration for Asterisk REST Interface.')
                    ->icon(Tabler::HttpConnect)
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('scheme')
                                    ->label('Scheme')
                                    ->options(['http' => 'HTTP', 'https' => 'HTTPS'])
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->prefixIcon(Tabler::ShieldLock),
                                TextInput::make('host')
                                    ->label('Host')
                                    ->placeholder('127.0.0.1')
                                    ->required()
                                    ->columnSpan(1)
                                    ->prefixIcon(Tabler::Network),
                                TextInput::make('port')
                                    ->label('Port')
                                    ->numeric()
                                    ->default(8088)
                                    ->required()
                                    ->prefixIcon(Tabler::Adjustments),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('username')
                                    ->label('Username')
                                    ->required()
                                    ->prefixIcon(Tabler::User),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->prefixIcon(Tabler::Key),
                            ]),
                    ]),
            ]);
    }
}

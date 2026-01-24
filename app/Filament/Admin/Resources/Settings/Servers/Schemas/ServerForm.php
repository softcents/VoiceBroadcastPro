<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\Servers\Schemas;

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
                                    ->placeholder('e.g. 127.0.0.1')
                                    ->required()
                                    ->columnSpan(1)
                                    ->prefixIcon(Tabler::Network),
                                TextInput::make('port')
                                    ->prefixIcon(Tabler::Adjustments)
                                    ->label('Port')
                                    ->placeholder('e.g. 8088')
                                    ->numeric()
                                    ->default(8088)
                                    ->required(),
                            ]),

                        Grid::make()
                            ->schema([
                                TextInput::make('username')
                                    ->label('Username')
                                    ->prefixIcon(Tabler::User)
                                    ->required()
                                    ->placeholder('Enter your username'),
                                TextInput::make('password')
                                    ->prefixIcon(Tabler::Key)
                                    ->label('Password')
                                    ->placeholder('Enter your password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn($operation) => $operation === 'create')
                                    ->dehydrated(fn($state, $operation) => $operation === 'create' || filled($state))
                                    ->helperText(fn($operation) => $operation === 'edit' ? 'Leave blank to keep existing password.' : null),
                            ]),
                    ]),
            ]);
    }
}

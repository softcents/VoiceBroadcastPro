<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Support\Str;
use LaraZeus\Tabler\Tabler;

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

                        Select::make('enabled')
                        ->label('Status')
                            ->boolean()
                            ->default(true)
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                Section::make()
                                    ->heading('ARI Connection')
                                    ->description('Configuration for Asterisk REST Interface (ARI) connection.')
                                    ->schema([
                                        Select::make('scheme')
                                            ->prefixIcon(Tabler::HttpConnect)
                                            ->label('Scheme')
                                            ->options([
                                                'http' => 'http',
                                                'https' => 'https',
                                            ])
                                            ->native(false)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                        TextInput::make('host')
                                            ->prefixIcon(Tabler::Server)
                                            ->label('Host')
                                            ->hint('IP address or domain of the Asterisk server.')
                                            ->required(),
                                        TextInput::make('port')
                                            ->prefixIcon(Tabler::DeviceHeartMonitor)
                                            ->label('Host')
                                            ->hint('Port number for the ARI connection.')
                                            ->numeric()
                                            ->default(8088)
                                            ->required(),
                                        TextInput::make('username')
                                            ->prefixIcon(Tabler::User)
                                            ->label('Username')
                                            ->hint('Username for ARI authentication.')
                                            ->required(),
                                        TextInput::make('password')
                                            ->prefixIcon(Tabler::Lock)
                                            ->label('Password')
                                            ->hint('Password for ARI authentication.')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                    ]),
                                Section::make()
                                    ->heading('CDR Database Connection')
                                    ->description('Configuration for Asterisk CDR database connection.')
                                    ->headerActions([
                                        Action::make('db_on_create')
                                            ->modal()
                                            ->outlined()
                                            ->iconButton()
                                            ->visible(fn($operation) => $operation === 'create')
                                            ->icon(Tabler::HelpCircle)
                                            ->size(Size::Small)
                                            ->tooltip('How to create CDR database user?')
                                            ->modalHeading('Create CDR Database User')
                                            ->modalDescription('Fill in the details below to generate the MySQL command to create a CDR database user with appropriate privileges. Click "Copy Command" to copy the generated command to your clipboard.')
                                            ->modalSubmitActionLabel('Copy Command')
                                            ->modalSubmitAction(fn (Action $action) => $action->hidden())
                                            ->schema([
                                                TextInput::make('username')
                                                    ->label('Username')
                                                    ->default('softcents')
                                                    ->afterStateUpdated(fn(Get $get, Set $set)  => $set('command', self::generateCommand($get('username'), $get('password'), $get('ip_address'))))
                                                    ->required(),
                                                TextInput::make('password')
                                                    ->label('Password')
                                                    ->debounce()
                                                    ->default(Str::password(16))
                                                    ->afterStateUpdated(fn(Get $get, Set $set)  => $set('command', self::generateCommand($get('username'), $get('password'), $get('ip_address'))))
                                                    ->required(),
                                                TextInput::make('ip_address')
                                                    ->label('IP Address')
                                                    ->default($_SERVER['SERVER_ADDR'] ?? '127.0.0.1')
                                                    ->live()
                                                    ->afterStateUpdated(fn(Get $get, Set $set)  => $set('command', self::generateCommand($get('username'), $get('password'), $get('ip_address'))))
                                                    ->required(),
                                                Textarea::make('command')
                                                    ->label('MySQL Command')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->default(fn(Get $get) => self::generateCommand($get('username'), $get('password'), $get('ip_address')))
                                                    ->disabled(),
                                            ]),
                                        Action::make('db_on_edit')
                                            ->modal()
                                            ->outlined()
                                            ->iconButton()
                                            ->visible(fn($operation) => $operation === 'edit')
                                            ->icon(Tabler::HelpCircle)
                                            ->size(Size::Small)
                                            ->tooltip('How to create CDR database user?')
                                            ->modalHeading('Create CDR Database User')
                                            ->modalDescription('Copy the MySQL command below to create a CDR database user with appropriate privileges based on the current configuration.')
                                            ->modalSubmitActionLabel('Copy Command')
                                            ->modalSubmitAction(fn (Action $action) => $action->hidden())
                                            ->schema(function ($record){
                                                return [
                                                    Textarea::make('command')
                                                        ->label('MySQL Command')
                                                        ->rows(3)
                                                        ->columnSpanFull()
                                                        ->default(fn(Get $get) => self::generateCommand($record->database_username, $record->database_password, $record->database_host))
                                                        ->disabled(),
                                                ];
                                            }),
                                    ])
                                    ->schema([
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
                                            ->revealable()
                                            ->required(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function generateCommand($username, $password, $ip): string
    {
        $command = "CREATE USER '{$username}'@'{$ip}' IDENTIFIED BY '{$password}';\n";
        $command .= "GRANT ALL PRIVILEGES ON asteriskcdrdb.* TO '{$username}'@'{$ip}';\n";
        $command .= 'FLUSH PRIVILEGES;';
        return $command;
    }
}

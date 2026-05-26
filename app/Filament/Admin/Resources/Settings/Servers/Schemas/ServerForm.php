<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\Servers\Schemas;

use App\Models\Server;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\CodeEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;
use Phiki\Grammar\Grammar;

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
                        TextInput::make('max_concurrency')
                            ->label('Max Concurrency')
                            ->placeholder('e.g. 100')
                            ->numeric()
                            ->default(100)
                            ->required()
                            ->prefixIcon(Tabler::Users),

                        Select::make('enabled')
                            ->label('Status')
                            ->boolean()
                            ->default(true)
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->prefixIcon(Tabler::Activity),
                    ]),

                Tabs::make()
                    ->columnSpan(2)
                    ->id('server-settings-tabs')
                    ->persistTab()
                    ->tabs([
                        Tabs\Tab::make('ARI Connection')
                            ->icon(Tabler::Link)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('ari_scheme')
                                            ->label('Scheme')
                                            ->options(['http' => 'HTTP', 'https' => 'HTTPS'])
                                            ->native(false)
                                            ->selectablePlaceholder(false)
                                            ->required()
                                            ->prefixIcon(Tabler::ShieldLock),
                                        TextInput::make('ari_host')
                                            ->label('Host')
                                            ->placeholder('e.g. 127.0.0.1')
                                            ->required()
                                            ->columnSpan(1)
                                            ->prefixIcon(Tabler::Network),
                                        TextInput::make('ari_port')
                                            ->prefixIcon(Tabler::Adjustments)
                                            ->label('Port')
                                            ->placeholder('e.g. 8088')
                                            ->numeric()
                                            ->default(8088)
                                            ->required(),
                                    ]),
                                Grid::make()
                                    ->schema([
                                        TextInput::make('ari_username')
                                            ->label('Username')
                                            ->prefixIcon(Tabler::User)
                                            ->required()
                                            ->placeholder('Enter your username'),
                                        TextInput::make('ari_password')
                                            ->prefixIcon(Tabler::Key)
                                            ->label('Password')
                                            ->placeholder('Enter your password')
                                            ->formatStateUsing(fn (?Server $record) => $record?->ari_password)
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Database Connection')
                            ->icon(Tabler::Database)
                            ->columns()
                            ->schema([
                                TextInput::make('database_host')
                                    ->label('Host')
                                    ->placeholder('e.g. 127.0.0.1')
                                    ->required()
                                    ->columnSpan(1)
                                    ->prefixIcon(Tabler::Network),
                                TextInput::make('database_port')
                                    ->prefixIcon(Tabler::Adjustments)
                                    ->label('Port')
                                    ->placeholder('e.g. 3306')
                                    ->numeric()
                                    ->default(8088)
                                    ->required(),
                                TextInput::make('database_username')
                                    ->prefixIcon(Tabler::User)
                                    ->label('Username')
                                    ->placeholder('Enter your username')
                                    ->required(),
                                TextInput::make('database_password')
                                    ->prefixIcon(Tabler::Key)
                                    ->label('Password')
                                    ->placeholder('Enter your password')
                                    ->formatStateUsing(fn (?Server $record) => $record?->database_password)
                                    ->password()
                                    ->revealable()
                                    ->required(),

                                CodeEntry::make('setup_remote_database')
                                    ->label('Setup Remote Database')
                                    ->state(sprintf('bash <(curl -fsSL %s)', url('/scripts/freepbx-db.sh')))
                                    ->grammar(Grammar::Shellscript)
                                    ->copyable()
                                    ->columnSpanFull(),

                            ]),
                    ]),
            ]);
    }
}

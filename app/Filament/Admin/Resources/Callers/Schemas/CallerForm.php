<?php

namespace App\Filament\Admin\Resources\Callers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CallerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->label('Server')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('caller_name')
                            ->label('Caller Name')
                            ->required(),
                        TextInput::make('caller_number')
                            ->label('Caller Number')
                            ->required(),
                        Select::make('users')
                            ->relationship('users', 'name')
                            ->label('Assigned Users')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->required(),
                    ])
            ]);
    }
}

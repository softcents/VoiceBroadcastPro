<?php

namespace App\Filament\Admin\Resources\Transactions\Schemas;

use App\Enums\TransactionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('User')
                            ->required(),
                        Select::make('type')
                            ->label('Type')
                            ->options(TransactionType::class)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('currency')
                            ->label('Currency')
                            ->required()
                            ->default('BDT'),
                        TextInput::make('description')
                            ->label('Description')
                            ->required(),
                        TextInput::make('reference_type')
                            ->label('Reference Type'),
                        TextInput::make('reference_id')
                            ->label('Reference ID')
                            ->numeric(),
                    ])
            ]);
    }
}

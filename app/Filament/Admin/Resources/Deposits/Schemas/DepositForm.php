<?php

namespace App\Filament\Admin\Resources\Deposits\Schemas;

use App\Enums\DepositStatus;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepositForm
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
                            ->required()
                            ->searchable()
                            ->preload()
                            ->optionsLimit(50),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('currency')
                            ->label('Currency')
                            ->required()
                            ->default('BDT')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('gateway')
                            ->label('Gateway')
                            ->options([
                                'piprapay' => 'Piprapay',
                                'bkash' => 'Bkash',
                                'rocket' => 'Rocket',
                                'nagad' => 'Nagad',
                                'upay' => 'Upay',
                                'bank' => 'Bank',
                                'cash' => 'Cash',
                            ])
                            ->searchable()
                            ->default('cash')
                            ->selectablePlaceholder(false)
                            ->required(),
                        TextInput::make('transaction_id')
                            ->label('Transaction ID'),
                        Select::make('status')
                            ->label('Status')
                            ->options(DepositStatus::class)
                            ->default('pending')
                            ->searchable()
                            ->selectablePlaceholder(false)
                            ->required(),
                        KeyValue::make('meta_data')
                            ->label('Meta Data'),
                    ])
            ]);
    }
}

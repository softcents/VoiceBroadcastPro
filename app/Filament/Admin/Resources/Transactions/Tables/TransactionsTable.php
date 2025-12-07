<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Deposits\DepositResource;
use App\Models\Deposit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn ($record) => CustomerResource::getUrl('edit', ['record' => $record->user_id])),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->prefix('৳ ')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_type')
                    ->label('Reference Type')
                    ->searchable(),
                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->numeric()
                    ->sortable()
                    ->url(fn ($record) => match ($record->reference_type) {
                        Deposit::class => DepositResource::getUrl('edit', ['record' => $record->reference_id]),
                        default => null,
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                //
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Transactions\Tables;

use App\Filament\User\Resources\Deposits\DepositResource;
use App\Models\Call;
use App\Models\Deposit;
use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
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
                    ->icon(Tabler::InfoCircle)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->searchable()
                    ->formatStateUsing(fn (Transaction $record) => match ($record->reference_type) {
                        Deposit::class => 'Deposit',
                        Call::class => 'Call',
                        default => 'N/A',
                    }),
                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->numeric()
                    ->sortable()
                    ->url(fn (Transaction $record) => match ($record->reference_type) {
                        Deposit::class => $record->reference ? DepositResource::getUrl('view', ['record' => $record->reference_id]) : 'javascript:void(0);',
                        default => null,
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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

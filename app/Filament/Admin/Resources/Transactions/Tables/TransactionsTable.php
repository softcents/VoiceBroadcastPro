<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Enums\TransactionType;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Deposits\DepositResource;
use App\Models\Deposit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn($record) => CustomerResource::getUrl('edit', ['record' => $record->user_id])),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value))
                    ->color(fn ($state) => match ($state) {
                        TransactionType::Deposit => Color::Green,
                        TransactionType::Expense => 'danger',
                        TransactionType::Refund => Color::Gray,
                    })
                    ->icon(fn ($state) => match ($state) {
                        TransactionType::Deposit => Heroicon::OutlinedArrowDownCircle,
                        TransactionType::Expense => Heroicon::OutlinedArrowUpCircle,
                        TransactionType::Refund => Heroicon::OutlinedArrowPath,
                    }),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->searchable(),
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
                    ->url(fn($record) => match ($record->reference_type) {
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

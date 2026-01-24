<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Finance\Deposits\Tables;

use App\Enums\DepositStatus;
use App\Enums\TransactionType;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Deposit;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DepositsTable
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
                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->searchable(),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state->value))
                    ->color(fn ($state) => match ($state) {
                        DepositStatus::Pending => Color::Yellow,
                        DepositStatus::Completed => Color::Green,
                        DepositStatus::Cancelled => 'danger',
                    })
                    ->icon(fn ($state) => match ($state) {
                        DepositStatus::Pending => Heroicon::OutlinedClock,
                        DepositStatus::Completed => Heroicon::OutlinedCheckCircle,
                        DepositStatus::Cancelled => Heroicon::OutlinedXCircle,
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
                ActionGroup::make([
                    Action::make('edit_status')
                        ->label('Edit Status')
                        ->icon(Heroicon::OutlinedPencil)
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(DepositStatus::class)
                                ->default(fn ($record) => $record->status)
                                ->required()
                                ->searchable()
                                ->selectablePlaceholder(false),
                        ])
                        ->modalWidth(Width::Small)
                        ->visible(fn ($record) => ! in_array($record->status, [DepositStatus::Completed, DepositStatus::Cancelled]))
                        ->action(function (Deposit $record, array $data) {
                            $newStatus = $data['status'];
                            if (! $newStatus instanceof DepositStatus) {
                                $newStatus = DepositStatus::tryFrom($newStatus);
                            }

                            // If transitioning to completed, add funds
                            if ($newStatus === DepositStatus::Completed && $record->status !== DepositStatus::Completed) {
                                $record->user->increment('balance', $record->amount);

                                Transaction::create([
                                    'user_id' => $record->user_id,
                                    'type' => TransactionType::Credit,
                                    'amount' => $record->amount,
                                    'currency' => $record->currency,
                                    'description' => 'Deposit via '.ucfirst($record->gateway),
                                    'reference_type' => Deposit::class,
                                    'reference_id' => $record->id,
                                ]);
                            }

                            $record->update(['status' => $newStatus]);
                        }),
                    DeleteAction::make()
                        ->label('Delete Deposit')
                        ->requiresConfirmation(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

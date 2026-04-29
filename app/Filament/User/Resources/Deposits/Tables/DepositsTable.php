<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Deposits\Tables;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Support\Payment\PaymentService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width(0)
                    ->alignCenter(),
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
                    ->badge(),
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
                    //                    Action::make('pay')
                    //                        ->label('Pay Now')
                    //                        ->icon(Heroicon::OutlinedCreditCard)
                    //                        ->color('success')
                    //                        ->visible(fn (Deposit $record) => $record->status === DepositStatus::Pending)
                    //                        ->action(function (Deposit $record) {
                    //                            try {
                    //                                $service = app(PaymentService::class);
                    //                                $result = $service->initiate($record);
                    //
                    //                                if (isset($result['url'])) {
                    //                                    redirect()->away($result['url']);
                    //                                }
                    //
                    //                                Notification::make()
                    //                                    ->title('Payment URL not found.')
                    //                                    ->danger()
                    //                                    ->send();
                    //
                    //                            } catch (Exception $e) {
                    //                                Notification::make()
                    //                                    ->title('Payment initiation failed')
                    //                                    ->body($e->getMessage())
                    //                                    ->danger()
                    //                                    ->send();
                    //                            }
                    //                        }),
                    Action::make('verify_deposit')
                        ->label('Verify Deposit')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('primary')
                        ->visible(fn (Deposit $record) => $record->status === DepositStatus::Pending)
                        ->action(function (Deposit $record) {
                            $service = app(PaymentService::class);
                            $paid = $service->verify($record);

                            if ($paid) {
                                $record->update(['status' => DepositStatus::Completed]);
                                $record->user->increment('balance', $record->amount);

                                Notification::make()
                                    ->title('Deposit verified and completed successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Deposit verification failed or still pending.')
                                    ->warning()
                                    ->send();
                            }
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

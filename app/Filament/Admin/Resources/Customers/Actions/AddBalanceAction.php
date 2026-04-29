<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Customers\Actions;

use App\Enums\DepositStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use LaraZeus\Tabler\Tabler;

final class AddBalanceAction
{
    public static function make(): Action
    {
        return Action::make('add_balance')
            ->icon(Tabler::MoneybagPlus)
            ->label('Add Balance')
            ->modalWidth(Width::Medium)
            ->schema([
                TextInput::make('amount')
                    ->prefix('BDT')
                    ->label('Amount')
                    ->placeholder('Enter Amount')
                    ->numeric()
                    ->required(),
            ])
            ->action(function (array $data, User $record) {
                $record->increment('balance', $data['amount']);

                $record->deposits()->create([
                    'amount' => $data['amount'],
                    'gateway' => 'admin_addition',
                    'status' => DepositStatus::Completed,
                ]);

                Notification::make()
                    ->title('Balance added successfully')
                    ->success()
                    ->send();
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Finance\Deposits\Pages;

use App\Enums\DepositStatus;
use App\Filament\User\Resources\Finance\Deposits\DepositResource;
use App\Support\Payment\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

final class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Make Deposit')
                ->modal()
                ->modalWidth(Width::Small)
                ->schema([
                    TextInput::make('amount')
                        ->prefix('BDT')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->minValue(50),
                ])
                ->action(function ($data) {
                    $deposit = Auth::user()->deposits()->create([
                        'amount' => $data['amount'],
                        'gateway' => 'piprapay',
                        'currency' => 'BDT',
                        'status' => DepositStatus::Pending,
                    ]);

                    $paymentService = app()->make(PaymentService::class);

                    $paymentData = $paymentService->initiate($deposit);

                    $deposit->update([
                        'transaction_id' => $paymentData['id'],
                        'meta_data' => ['checkout_url' => $paymentData['url']],
                    ]);

                    return redirect($paymentData['url']);
                }),
        ];
    }
}

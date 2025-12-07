<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Deposits\Pages;

use App\Filament\Admin\Resources\Deposits\DepositResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    protected function afterCreate(): void
    {
        $deposit = $this->record;

        if ($deposit->status === \App\Enums\DepositStatus::Completed) {
            $deposit->user->increment('balance', $deposit->amount * 100);

            \App\Models\Transaction::create([
                'user_id' => $deposit->user_id,
                'type' => \App\Enums\TransactionType::Deposit,
                'amount' => $deposit->amount * 100, // Store in cents
                'currency' => $deposit->currency,
                'description' => 'Deposit via '.ucfirst($deposit->gateway),
                'reference_type' => \App\Models\Deposit::class,
                'reference_id' => $deposit->id,
            ]);
        }
    }
}

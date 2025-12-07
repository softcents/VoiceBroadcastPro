<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\DepositStatus;
use App\Enums\TransactionType;
use App\Models\Deposit;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\PipraPayGateway;
use InvalidArgumentException;

final class PaymentService
{
    public function getGateway(string $gatewayName): PaymentGateway
    {
        return match ($gatewayName) {
            'piprapay' => app(PipraPayGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}"),
        };
    }

    public function initiate(Deposit $deposit): array
    {
        $gateway = $this->getGateway($deposit->gateway);

        return $gateway->initiatePayment($deposit);
    }

    public function verify(Deposit $deposit): bool
    {
        $gateway = $this->getGateway($deposit->gateway);

        return $gateway->verifyPayment($deposit->transaction_id);
    }

    public function confirm(Deposit $deposit): void
    {
        if ($deposit->status === DepositStatus::Completed) {
            return;
        }

        $deposit->update(['status' => DepositStatus::Completed]);

        $deposit->user->transactions()->create([
            'type' => TransactionType::Deposit,
            'amount' => $deposit->amount,
            'currency' => $deposit->currency,
            'description' => 'Deposit via '.$deposit->gateway,
            'reference_type' => Deposit::class,
            'reference_id' => $deposit->id,
        ]);

        $deposit->user->increment('balance', $deposit->amount * 100);
    }
}

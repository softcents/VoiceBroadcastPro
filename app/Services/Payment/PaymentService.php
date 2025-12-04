<?php

namespace App\Services\Payment;

use App\Models\Deposit;
use App\Services\Payment\Gateways\PipraPayGateway;
use InvalidArgumentException;

class PaymentService
{
    public function getGateway(string $gatewayName): PaymentGatewayInterface
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
        if ($deposit->status === \App\Enums\DepositStatus::Completed) {
            return;
        }

        $deposit->update(['status' => \App\Enums\DepositStatus::Completed]);

        $deposit->user->transactions()->create([
            'type' => \App\Enums\TransactionType::Deposit,
            'amount' => $deposit->amount,
            'currency' => $deposit->currency,
            'description' => 'Deposit via ' . $deposit->gateway,
            'reference_type' => Deposit::class,
            'reference_id' => $deposit->id,
        ]);

        $deposit->user->increment('balance', $deposit->amount);
    }
}

<?php

namespace App\Services\Payment;

use App\Models\Deposit;

interface PaymentGatewayInterface
{
    public function initiatePayment(Deposit $deposit): array;
    public function verifyPayment(string $paymentId): bool;
}

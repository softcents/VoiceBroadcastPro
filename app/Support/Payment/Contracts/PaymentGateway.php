<?php

declare(strict_types=1);

namespace App\Support\Payment\Contracts;

use App\Models\Deposit;

interface PaymentGateway
{
    public function initiatePayment(Deposit $deposit): array;

    public function verifyPayment(string $paymentId): bool;
}

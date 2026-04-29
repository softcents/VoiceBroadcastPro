<?php

declare(strict_types=1);

namespace App\Support\Payment\Gateways;

use App\Models\Deposit;
use App\Support\Payment\Contracts\PaymentGateway;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class PipraPayGateway implements PaymentGateway
{
    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function initiatePayment(Deposit $deposit): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'MHS-PIPRAPAY-API-KEY' => config('services.piprapay.api_key'),
        ])->post(config('services.piprapay.base_url').'/checkout/redirect', [
            'full_name' => $deposit->user->name,
            'email_address' => $deposit->user->email,
            'mobile_number' => $deposit->user->phone ?? '01000000000',
            'amount' => (string) $deposit->amount,
            'currency' => $deposit->currency,
            'metadata' => [
                'deposit_id' => $deposit->id,
            ],
            'return_url' => route('payments.piprapay.callback', ['deposit' => $deposit->id]),
            'webhook_url' => route('webhooks.piprapay', ['deposit' => $deposit->id]),
        ]);

        if ($response->successful() && $response->json('pp_url')) {
            return [
                'url' => $response->json('pp_url'),
                'id' => $response->json('pp_id'),
            ];
        }

        throw new Exception('Failed to initiate payment: '.$response->body());
    }

    public function verifyPayment(string $paymentId): bool
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'MHS-PIPRAPAY-API-KEY' => config('services.piprapay.api_key'),
        ])->post(config('services.piprapay.base_url').'/verify-payment', [
            'pp_id' => $paymentId,
        ]);

        return $response->successful() && $response->json('status') === 'completed';
    }
}

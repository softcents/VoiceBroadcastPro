<?php

namespace App\Services\Payment\Gateways;

use App\Models\Deposit;
use App\Services\Payment\PaymentGatewayInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PipraPayGateway implements PaymentGatewayInterface
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
            'mh-piprapay-api-key' => config('services.piprapay.api_key'),
        ])->post(config('services.piprapay.base_url') . '/create-charge', [
            'full_name' => $deposit->user->name,
            'email_mobile' => $deposit->user->email,
            'amount' => $deposit->amount, // Accessor returns float (e.g., 100.00)
            'redirect_url' => route('payments.piprapay.callback', ['deposit' => $deposit->id]),
            'return_type' => 'GET',
            'cancel_url' => route('payments.piprapay.cancel', ['deposit' => $deposit->id]),
            'webhook_url' => route('webhooks.piprapay', ['deposit' => $deposit->id]),
            'currency' => $deposit->currency,
            'metadata' => [
                'deposit_id' => $deposit->id,
            ]
        ]);

        if ($response->successful() && $response->json('status')) {
            return [
                'url' => $response->json('pp_url'),
                'id' => $response->json('pp_id'),
            ];
        }

        throw new Exception('Failed to initiate payment: ' . $response->body());
    }

    public function verifyPayment(string $paymentId): bool
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'mh-piprapay-api-key' => config('services.piprapay.api_key'),
        ])->post(config('services.piprapay.base_url') . '/verify-payments', [
            'pp_id' => $paymentId,
        ]);

        return $response->successful() && $response->json('status') === 'completed';
    }
}

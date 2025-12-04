<?php

use App\Models\Deposit;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\actingAs;

test('can list deposits', function () {
    $user = User::factory()->create();
    Deposit::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/deposits');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can initiate deposit', function () {
    $user = User::factory()->create();

    // Mock PipraPay API
    Http::fake([
        'pay.frolax.agency/api/create-charge' => Http::response([
            'status' => true,
            'pp_id' => 12345,
            'pp_url' => 'https://pay.frolax.agency/payment/12345',
        ], 200),
    ]);

    $response = actingAs($user)->postJson('/api/deposits', [
        'amount' => 100,
        'gateway' => 'piprapay',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.amount', 100)
        ->assertJsonPath('data.status', \App\Enums\DepositStatus::Pending->value)
        ->assertJsonPath('data.transaction_id', 12345)
        ->assertJsonPath('data.checkout_url', 'https://pay.frolax.agency/payment/12345');

    $this->assertDatabaseHas('deposits', [
        'user_id' => $user->id,
        'amount' => 10000,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => \App\Enums\DepositStatus::Pending->value,
        'transaction_id' => '12345',
    ]);
});

test('can verify deposit', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => \App\Enums\DepositStatus::Pending,
        'transaction_id' => '12345',
    ]);

    // Mock PipraPay Verify API
    Http::fake([
        'pay.frolax.agency/api/verify-payments' => Http::response([
            'status' => 'completed',
            'message' => 'Payment verified successfully',
        ], 200),
    ]);
    
    $response = actingAs($user)->postJson("/api/deposits/{$deposit->id}/verify");

    $response->assertOk()
        ->assertJsonPath('data.status', \App\Enums\DepositStatus::Completed->value);

    $this->assertDatabaseHas('deposits', [
        'id' => $deposit->id,
        'status' => \App\Enums\DepositStatus::Completed->value,
    ]);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => \App\Enums\TransactionType::Deposit->value,
        'amount' => 10000,
        'currency' => 'BDT',
        'reference_type' => Deposit::class,
        'reference_id' => $deposit->id,
    ]);
});

<?php

declare(strict_types=1);

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('callback redirects to success on successful verification', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => DepositStatus::Pending,
        'transaction_id' => '12345',
    ]);

    Http::fake([
        'pay.frolax.agency/api/verify-payments' => Http::response([
            'status' => 'completed',
        ], 200),
    ]);

    $response = get(route('payments.piprapay.callback', $deposit));

    $response->assertRedirect(route('payments.success'));

    expect($deposit->refresh()->status)->toBe(DepositStatus::Completed);
});

test('callback redirects to failed on failed verification', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => DepositStatus::Pending,
        'transaction_id' => '12345',
    ]);

    Http::fake([
        'pay.frolax.agency/api/verify-payments' => Http::response([
            'status' => 'failed',
        ], 200),
    ]);

    $response = get(route('payments.piprapay.callback', $deposit));

    $response->assertRedirect(route('payments.failed'));

    expect($deposit->refresh()->status)->toBe(DepositStatus::Failed);
});

test('cancel updates status and redirects', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => DepositStatus::Pending,
        'transaction_id' => '12345',
    ]);

    $response = get(route('payments.piprapay.cancel', $deposit));

    $response->assertRedirect(route('payments.cancel'));

    expect($deposit->refresh()->status)->toBe(DepositStatus::Cancelled);
});

test('webhook confirms payment', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'currency' => 'BDT',
        'gateway' => 'piprapay',
        'status' => DepositStatus::Pending,
        'transaction_id' => '12345',
    ]);

    Http::fake([
        'pay.frolax.agency/api/verify-payments' => Http::response([
            'status' => 'completed',
        ], 200),
    ]);

    $response = post(route('webhooks.piprapay', $deposit));

    $response->assertOk()
        ->assertJson(['status' => 'success']);

    expect($deposit->refresh()->status)->toBe(DepositStatus::Completed);
});

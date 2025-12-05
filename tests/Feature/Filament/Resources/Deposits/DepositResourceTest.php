<?php

use App\Filament\Admin\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\ListDeposits;
use App\Models\Deposit;
use App\Models\User;
use App\Enums\DepositStatus;
use App\Enums\UserType;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

it('updates user balance when deposit is created with completed status', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $user = User::factory()->create(['type' => UserType::User, 'balance' => 0]);

    actingAs($admin);

    Livewire::test(CreateDeposit::class)
        ->fillForm([
            'user_id' => $user->id,
            'amount' => 100,
            'currency' => 'BDT',
            'gateway' => 'cash',
            'status' => DepositStatus::Completed->value,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $user->refresh();
    expect((int) $user->getRawOriginal('balance'))->toBe(10000); // 100 * 100
});

it('does not update user balance when deposit is created with pending status', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $user = User::factory()->create(['type' => UserType::User, 'balance' => 0]);

    actingAs($admin);

    Livewire::test(CreateDeposit::class)
        ->fillForm([
            'user_id' => $user->id,
            'amount' => 100,
            'currency' => 'BDT',
            'gateway' => 'cash',
            'status' => DepositStatus::Pending->value,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $user->refresh();
    expect((int) $user->getRawOriginal('balance'))->toBe(0);
});

it('updates status and balance via table action', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $user = User::factory()->create(['type' => UserType::User, 'balance' => 0]);
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'amount' => 50,
        'status' => DepositStatus::Pending,
    ]);

    actingAs($admin);

    Livewire::test(ListDeposits::class)
        ->callTableAction('edit_status', $deposit, data: [
            'status' => DepositStatus::Completed->value,
        ])
        ->assertHasNoErrors();

    $deposit->refresh();
    $user->refresh();

    expect($deposit->status)->toBe(DepositStatus::Completed);
    expect((int) $user->getRawOriginal('balance'))->toBe(5000); // 50 * 100
});

it('disables update status action for completed deposits', function () {
     $admin = User::factory()->create(['type' => UserType::Admin]);
    $user = User::factory()->create(['type' => UserType::User]);
    $deposit = Deposit::factory()->create([
        'user_id' => $user->id,
        'status' => DepositStatus::Completed,
    ]);

    actingAs($admin);

    Livewire::test(ListDeposits::class)
        ->assertTableActionDisabled('edit_status', $deposit);
});

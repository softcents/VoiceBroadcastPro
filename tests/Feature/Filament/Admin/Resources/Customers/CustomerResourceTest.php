<?php

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Enums\UserType;
use App\Models\User;
use Livewire\Livewire;

it('can view customer info', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['type' => UserType::Admin]);

    $this->actingAs($admin);

    Livewire::test(App\Filament\Admin\Resources\Customers\Pages\ViewCustomer::class, [
        'record' => $user->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee($user->name)
        ->assertSee($user->email);
});

<?php

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Customers\Pages\EditCustomer;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

it('does not update password if left empty during customer edit', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $customer = User::factory()->create([
        'type' => UserType::User,
        'password' => Hash::make('original-password'),
    ]);
    
    $originalPasswordHash = $customer->password;

    actingAs($admin);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => $customer->email,
            'phone' => '+8801711223344', // Valid BD phone
            'password' => '', // Empty password
        ])
        ->call('save')
        ->assertHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toBe('Updated Name');
    // The issue is that the password changes (likely to hashed empty string or something else)
    // We expect it to remain the same
    expect(Hash::check('original-password', $customer->password))->toBeTrue();
});

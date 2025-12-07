<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('can get account details', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->getJson('/api/account');

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.email', $user->email);
});

test('can update profile', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->putJson('/api/account', [
        'name' => 'New Name',
        'email' => 'newemail@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', 'newemail@example.com');

    assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'newemail@example.com',
    ]);
});

test('can change password', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $response = actingAs($user)->putJson('/api/account/password', [
        'current_password' => 'password',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertOk();

    expect(Hash::check('newpassword', $user->fresh()->password))->toBeTrue();
});

test('can get balance', function () {
    $user = User::factory()->create([
        'balance' => 10,
    ]);

    $response = actingAs($user)->getJson('/api/account/balance');

    $response->assertOk()
        ->assertJsonPath('data.balance', 10);
});

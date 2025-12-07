<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admin can access admin panel', function () {
    $admin = User::factory()->create([
        'type' => UserType::Admin,
    ]);

    actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('user cannot access admin panel', function () {
    $user = User::factory()->create([
        'type' => UserType::User,
    ]);

    actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('user can access user panel', function () {
    $user = User::factory()->create([
        'type' => UserType::User,
    ]);

    actingAs($user)
        ->get('/user')
        ->assertSuccessful();
});

test('admin cannot access user panel', function () {
    $admin = User::factory()->create([
        'type' => UserType::Admin,
    ]);

    actingAs($admin)
        ->get('/user')
        ->assertForbidden();
});

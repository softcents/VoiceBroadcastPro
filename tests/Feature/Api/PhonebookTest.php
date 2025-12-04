<?php

use App\Models\Phonebook;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list phonebooks', function () {
    $user = User::factory()->create();
    Phonebook::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/phonebooks');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create phonebook', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/phonebooks', [
        'name' => 'My Phonebook',
        'description' => 'My Description',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Phonebook')
        ->assertJsonPath('data.description', 'My Description');

    assertDatabaseHas('phonebooks', [
        'user_id' => $user->id,
        'name' => 'My Phonebook',
        'description' => 'My Description',
    ]);
});

test('can show phonebook', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/phonebooks/{$phonebook->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $phonebook->id);
});

test('cannot show others phonebook', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->getJson("/api/phonebooks/{$phonebook->id}");

    $response->assertForbidden();
});

test('can update phonebook', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/phonebooks/{$phonebook->id}", [
        'name' => 'Updated Name',
        'description' => 'Updated Description',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.description', 'Updated Description');

    assertDatabaseHas('phonebooks', [
        'id' => $phonebook->id,
        'name' => 'Updated Name',
        'description' => 'Updated Description',
    ]);
});

test('cannot update others phonebook', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/phonebooks/{$phonebook->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertForbidden();
});

test('can delete phonebook', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/phonebooks/{$phonebook->id}");

    $response->assertNoContent();

    assertDatabaseMissing('phonebooks', [
        'id' => $phonebook->id,
    ]);
});

test('cannot delete others phonebook', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/phonebooks/{$phonebook->id}");

    $response->assertForbidden();
});

<?php

use App\Models\ContactGroup;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list contact groups', function () {
    $user = User::factory()->create();
    ContactGroup::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/contact-groups');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create contact group', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/contact-groups', [
        'name' => 'My Group',
        'description' => 'Test Description',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Group');

    assertDatabaseHas('contact_groups', [
        'user_id' => $user->id,
        'name' => 'My Group',
    ]);
});

test('can show contact group', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/contact-groups/{$group->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $group->id);
});

test('cannot show others contact group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->getJson("/api/contact-groups/{$group->id}");

    $response->assertForbidden();
});

test('can update contact group', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/contact-groups/{$group->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');

    assertDatabaseHas('contact_groups', [
        'id' => $group->id,
        'name' => 'Updated Name',
    ]);
});

test('cannot update others contact group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/contact-groups/{$group->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertForbidden();
});

test('can delete contact group', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/contact-groups/{$group->id}");

    $response->assertNoContent();

    assertDatabaseMissing('contact_groups', [
        'id' => $group->id,
    ]);
});

test('cannot delete others contact group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/contact-groups/{$group->id}");

    $response->assertForbidden();
});

<?php

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list contacts', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);
    Contact::factory()->count(3)->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->getJson('/api/contacts');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter contacts by group', function () {
    $user = User::factory()->create();
    $group1 = ContactGroup::factory()->create(['user_id' => $user->id]);
    $group2 = ContactGroup::factory()->create(['user_id' => $user->id]);
    Contact::factory()->count(2)->create(['contact_group_id' => $group1->id]);
    Contact::factory()->count(1)->create(['contact_group_id' => $group2->id]);

    $response = actingAs($user)->getJson("/api/contacts?contact_group_id={$group1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can create contact', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->postJson('/api/contacts', [
        'contact_group_id' => $group->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+8801712345678',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.first_name', 'John');

    assertDatabaseHas('contacts', [
        'contact_group_id' => $group->id,
        'first_name' => 'John',
    ]);
});

test('cannot create contact in others group', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->postJson('/api/contacts', [
        'contact_group_id' => $group->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+8801712345678',
    ]);

    $response->assertNotFound();
});

test('can show contact', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->getJson("/api/contacts/{$contact->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $contact->id);
});

test('cannot show others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->getJson("/api/contacts/{$contact->id}");

    $response->assertForbidden();
});

test('can update contact', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->putJson("/api/contacts/{$contact->id}", [
        'first_name' => 'Jane',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.first_name', 'Jane');

    assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'first_name' => 'Jane',
    ]);
});

test('cannot update others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->putJson("/api/contacts/{$contact->id}", [
        'first_name' => 'Jane',
    ]);

    $response->assertForbidden();
});

test('can delete contact', function () {
    $user = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->deleteJson("/api/contacts/{$contact->id}");

    $response->assertNoContent();

    assertDatabaseMissing('contacts', [
        'id' => $contact->id,
    ]);
});

test('cannot delete others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = ContactGroup::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['contact_group_id' => $group->id]);

    $response = actingAs($user)->deleteJson("/api/contacts/{$contact->id}");

    $response->assertForbidden();
});

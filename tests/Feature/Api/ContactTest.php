<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Phonebook;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list contacts', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);
    Contact::factory()->count(3)->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->getJson('/api/contacts');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter contacts by phonebook', function () {
    $user = User::factory()->create();
    $phonebook1 = Phonebook::factory()->create(['user_id' => $user->id]);
    $phonebook2 = Phonebook::factory()->create(['user_id' => $user->id]);
    Contact::factory()->create(['phonebook_id' => $phonebook1->id]);
    Contact::factory()->create(['phonebook_id' => $phonebook2->id]);

    $response = actingAs($user)->getJson("/api/contacts?phonebook_id={$phonebook1->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('can create contact', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->postJson('/api/contacts', [
        'phonebook_id' => $phonebook->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+8801712345678',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.last_name', 'Doe');

    assertDatabaseHas('contacts', [
        'phonebook_id' => $phonebook->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+8801712345678',
    ]);
});

test('cannot create contact in others phonebook', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->postJson('/api/contacts', [
        'phonebook_id' => $phonebook->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+8801712345678',
    ]);

    $response->assertNotFound();
});

test('can show contact', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->getJson("/api/contacts/{$contact->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $contact->id);
});

test('cannot show others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->getJson("/api/contacts/{$contact->id}");

    $response->assertForbidden();
});

test('can update contact', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->putJson("/api/contacts/{$contact->id}", [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.last_name', 'Doe');

    assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
});

test('cannot update others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->putJson("/api/contacts/{$contact->id}", [
        'first_name' => 'Jane',
    ]);

    $response->assertForbidden();
});

test('can delete contact', function () {
    $user = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->deleteJson("/api/contacts/{$contact->id}");

    $response->assertNoContent();

    assertDatabaseMissing('contacts', [
        'id' => $contact->id,
    ]);
});

test('cannot delete others contact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $phonebook = Phonebook::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create(['phonebook_id' => $phonebook->id]);

    $response = actingAs($user)->deleteJson("/api/contacts/{$contact->id}");

    $response->assertForbidden();
});

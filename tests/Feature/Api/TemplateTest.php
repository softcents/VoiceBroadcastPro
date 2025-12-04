<?php

use App\Models\Template;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list templates', function () {
    $user = User::factory()->create();
    Template::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/templates');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create template', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/templates', [
        'name' => 'My Template',
        'content' => 'Hello World',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Template')
        ->assertJsonPath('data.content', 'Hello World');

    assertDatabaseHas('templates', [
        'user_id' => $user->id,
        'name' => 'My Template',
        'content' => 'Hello World',
    ]);
});

test('can show template', function () {
    $user = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/templates/{$template->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $template->id);
});

test('cannot show others template', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->getJson("/api/templates/{$template->id}");

    $response->assertForbidden();
});

test('can update template', function () {
    $user = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/templates/{$template->id}", [
        'name' => 'Updated Name',
        'content' => 'Updated Content',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.content', 'Updated Content');

    assertDatabaseHas('templates', [
        'id' => $template->id,
        'name' => 'Updated Name',
        'content' => 'Updated Content',
    ]);
});

test('cannot update others template', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/templates/{$template->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertForbidden();
});

test('can delete template', function () {
    $user = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/templates/{$template->id}");

    $response->assertNoContent();

    assertDatabaseMissing('templates', [
        'id' => $template->id,
    ]);
});

test('cannot delete others template', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $template = Template::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/templates/{$template->id}");

    $response->assertForbidden();
});

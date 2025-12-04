<?php

use App\Enums\AudioType;
use App\Models\Audio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list audios', function () {
    $user = User::factory()->create();
    Audio::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/audios');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create audio (tts)', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/audios', [
        'title' => 'Test Audio',
        'type' => AudioType::TTS->value,
        'message' => 'Hello World',
        'language' => \App\Enums\AudioLanguage::BnBD->value,
        'gender' => \App\Enums\AudioGender::Male->value,
        'artist' => \App\Enums\AudioArtist::BnBdPradeepNeural->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Test Audio')
        ->assertJsonPath('data.type', AudioType::TTS->value)
        ->assertJsonPath('data.approval', \App\Enums\AudioApproval::Pending->value)
        ->assertJsonPath('data.message', 'Hello World')
        ->assertJsonPath('data.language', \App\Enums\AudioLanguage::BnBD->value)
        ->assertJsonPath('data.artist', \App\Enums\AudioArtist::BnBdPradeepNeural->value);

    assertDatabaseHas('audios', [
        'title' => 'Test Audio',
        'type' => AudioType::TTS->value,
        'approval' => \App\Enums\AudioApproval::Pending->value,
        'message' => 'Hello World',
        'language' => \App\Enums\AudioLanguage::BnBD->value,
    ]);
});

test('can create audio (upload)', function () {
    \Illuminate\Support\Facades\Queue::fake();
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    $response = actingAs($user)->postJson('/api/audios', [
        'title' => 'Test Upload',
        'type' => AudioType::Record->value,
        'file' => $file,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Test Upload')
        ->assertJsonPath('data.type', AudioType::Record->value);

    $audio = Audio::where('title', 'Test Upload')->first();
    Storage::disk('public')->assertExists($audio->original_path); // Assuming default disk is public or linked
});

test('can show audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/audios/{$audio->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $audio->id);
});

test('can update audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/audios/{$audio->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    assertDatabaseHas('audios', [
        'id' => $audio->id,
        'title' => 'Updated Title',
    ]);
});

test('cannot update others audio', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/audios/{$audio->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertForbidden();
});

test('can delete audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/audios/{$audio->id}");

    $response->assertNoContent();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/audios/{$audio->id}");

    $response->assertForbidden();
});

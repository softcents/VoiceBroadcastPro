<?php

declare(strict_types=1);

use App\Enums\AudioApproval;
use App\Enums\AudioType;
use App\Models\Audio;
use App\Models\TTSArtist;
use App\Models\TTSLanguage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('can list audio', function () {
    $user = User::factory()->create();
    Audio::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/audio');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create audio (tts)', function () {
    $user = User::factory()->create();
    $language = TTSLanguage::factory()->create(['code' => 'bn-BD']);
    $artist = TTSArtist::factory()->create([
        'tts_language_id' => $language->id,
        'code' => 'bn-BD-PradeepNeural',
    ]);

    $response = actingAs($user)->postJson('/api/audio', [
        'title' => 'Test Audio',
        'type' => AudioType::TTS->value,
        'message' => 'Hello World',
        'tts_artist_id' => $artist->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Test Audio')
        ->assertJsonPath('data.type', AudioType::TTS->value)
        ->assertJsonPath('data.approval', AudioApproval::Pending->value)
        ->assertJsonPath('data.message', 'Hello World')
        ->assertJsonPath('data.tts_artist.id', $artist->id);

    assertDatabaseHas('audio', [
        'title' => 'Test Audio',
        'type' => AudioType::TTS->value,
        'approval' => AudioApproval::Pending->value,
        'message' => 'Hello World',
        'tts_artist_id' => $artist->id,
    ]);
});

test('can create audio (upload)', function () {
    Illuminate\Support\Facades\Queue::fake();
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    $response = actingAs($user)->postJson('/api/audio', [
        'title' => 'Test Upload',
        'type' => AudioType::Upload->value,
        'file' => $file,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Test Upload')
        ->assertJsonPath('data.type', AudioType::Upload->value);

    $audio = Audio::where('title', 'Test Upload')->first();
    Storage::disk('public')->assertExists($audio->original_path); // Assuming default disk is public or linked
});

test('can show audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/audio/{$audio->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $audio->id);
});

test('can update audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->putJson("/api/audio/{$audio->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    assertDatabaseHas('audio', [
        'id' => $audio->id,
        'title' => 'Updated Title',
    ]);
});

test('cannot update others audio', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/audio/{$audio->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertForbidden();
});

test('can delete audio', function () {
    $user = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/audio/{$audio->id}");

    $response->assertNoContent();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $audio = Audio::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/audio/{$audio->id}");

    $response->assertForbidden();
});

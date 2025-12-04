<?php

use App\Models\AudioRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('can list audio records', function () {
    $user = User::factory()->create();
    AudioRecord::factory()->count(3)->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson('/api/audio-records');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create audio record', function () {
    Storage::fake();
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/audio-records', [
        'title' => 'My Recording',
        'files' => [
            UploadedFile::fake()->create('audio.mp3', 100, 'audio/mpeg'),
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'My Recording');

    assertDatabaseHas('audio_records', [
        'user_id' => $user->id,
        'title' => 'My Recording',
    ]);

    assertDatabaseHas('audio_files', [
        'name' => 'audio.mp3',
    ]);
});

test('can show audio record', function () {
    $user = User::factory()->create();
    $record = AudioRecord::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->getJson("/api/audio-records/{$record->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id);
});

test('cannot show others audio record', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->getJson("/api/audio-records/{$record->id}");

    $response->assertForbidden();
});

test('can update audio record', function () {
    $user = User::factory()->create();
    $record = AudioRecord::factory()->create([
        'user_id' => $user->id,
        'status' => \App\Enums\AudioRecordStatus::Pending,
    ]);

    $response = actingAs($user)->putJson("/api/audio-records/{$record->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    assertDatabaseHas('audio_records', [
        'id' => $record->id,
        'title' => 'Updated Title',
    ]);
});

test('cannot update others audio record', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->putJson("/api/audio-records/{$record->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertForbidden();
});

test('can delete audio record', function () {
    $user = User::factory()->create();
    $record = AudioRecord::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)->deleteJson("/api/audio-records/{$record->id}");

    $response->assertNoContent();

    assertDatabaseMissing('audio_records', [
        'id' => $record->id,
    ]);
});

test('cannot delete others audio record', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($user)->deleteJson("/api/audio-records/{$record->id}");

    $response->assertForbidden();
});

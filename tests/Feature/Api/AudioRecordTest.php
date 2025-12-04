<?php

namespace Tests\Feature\Api;

use App\Models\AudioRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_audio_records(): void
    {
        $user = User::factory()->create();
        AudioRecord::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/audio-records');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_audio_record(): void
    {
        \Illuminate\Support\Facades\Storage::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/audio-records', [
            'title' => 'My Recording',
            'files' => [
                \Illuminate\Http\UploadedFile::fake()->create('audio.mp3', 100, 'audio/mpeg'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My Recording');

        $this->assertDatabaseHas('audio_records', [
            'user_id' => $user->id,
            'title' => 'My Recording',
        ]);

        $this->assertDatabaseHas('audio_files', [
            'name' => 'audio.mp3',
        ]);
    }

    public function test_can_show_audio_record(): void
    {
        $user = User::factory()->create();
        $record = AudioRecord::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/audio-records/{$record->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $record->id);
    }

    public function test_cannot_show_others_audio_record(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson("/api/audio-records/{$record->id}");

        $response->assertForbidden();
    }

    public function test_can_update_audio_record(): void
    {
        $user = User::factory()->create();
        $record = AudioRecord::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\AudioRecordStatus::Pending,
        ]);

        $response = $this->actingAs($user)->putJson("/api/audio-records/{$record->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('audio_records', [
            'id' => $record->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_cannot_update_others_audio_record(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->putJson("/api/audio-records/{$record->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertForbidden();
    }

    public function test_can_delete_audio_record(): void
    {
        $user = User::factory()->create();
        $record = AudioRecord::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/audio-records/{$record->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('audio_records', [
            'id' => $record->id,
        ]);
    }

    public function test_cannot_delete_others_audio_record(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AudioRecord::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->deleteJson("/api/audio-records/{$record->id}");

        $response->assertForbidden();
    }
}

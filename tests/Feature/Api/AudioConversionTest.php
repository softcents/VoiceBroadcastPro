<?php

use App\Enums\AudioType;
use App\Jobs\ConvertAudio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;

test('it dispatches conversion job on upload', function () {
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    $response = actingAs($user)->postJson('/api/audio', [
        'title' => 'Test Upload',
        'type' => AudioType::Upload->value,
        'file' => $file,
    ]);

    $response->assertCreated();

    Queue::assertPushed(ConvertAudio::class);
});

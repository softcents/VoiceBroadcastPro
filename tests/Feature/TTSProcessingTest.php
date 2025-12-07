<?php

declare(strict_types=1);

use App\Enums\AudioTTSStatus;
use App\Enums\AudioType;
use App\Jobs\GenerateAudio;
use App\Models\Audio;
use App\Models\TTSArtist;
use App\Models\TTSLanguage;
use App\Models\User;
use App\Services\TTS\Contracts\TTSDriver;
use App\Services\TTS\TTSManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

uses(RefreshDatabase::class);

it('processes tts for audio', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    // Create Language and Artist
    $language = TTSLanguage::create(['name' => 'English', 'code' => 'en-US', 'engine' => 'azure', 'enabled' => true]);
    $artist = TTSArtist::create(['name' => 'Jenny', 'code' => 'en-US-JennyNeural', 'gender' => 'female', 'enabled' => true, 'tts_language_id' => $language->id]);

    $audio = Audio::create([
        'user_id' => $user->id,
        'title' => 'Test TTS',
        'type' => AudioType::TTS,
        'message' => 'Hello World',
        'tts_artist_id' => $artist->id,
        'tts_status' => AudioTTSStatus::Pending,
    ]);

    // Mock TTS Driver
    $mockDriver = Mockery::mock(TTSDriver::class);
    $mockDriver->shouldReceive('speak')
        ->once()
        ->with('Hello World', 'en-US-JennyNeural', 'en-US')
        ->andReturn('fake-audio-content');

    // Mock TTS Manager
    $this->mock(TTSManager::class, function ($mock) use ($mockDriver) {
        $mock->shouldReceive('driver')
            ->with('azure')
            ->once()
            ->andReturn($mockDriver);
    });

    // Mock FFMpeg duration
    FFMpeg::shouldReceive('open')
        ->once()
        ->andReturnSelf();
    FFMpeg::shouldReceive('getDurationInSeconds')
        ->once()
        ->andReturn(5.5);

    // Run Job
    new GenerateAudio($audio->id)->handle(app(TTSManager::class));

    // Assertions
    $audio->refresh();
    expect($audio->tts_status)
        ->toBe(AudioTTSStatus::Completed)
        ->and($audio->duration)
        ->toBe(5.5)
        ->and($audio->converted_path)
        ->not
        ->toBeNull();

    Storage::disk('local')->assertExists($audio->converted_path);
});

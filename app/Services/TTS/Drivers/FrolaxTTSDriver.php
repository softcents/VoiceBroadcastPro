<?php

declare(strict_types=1);

namespace App\Services\TTS\Drivers;

use App\Services\TTS\Contracts\TTSDriver;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FrolaxTTSDriver implements TTSDriver
{
    public function __construct() {}

    public function speak(string $text, string $language, string $gender, string $artist, string $format = 'mp3'): string
    {
        $url = 'http://localhost:8000/generate';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->post($url, [
                'text' => $text,
                'lang' => $language,
                'gender' => $gender,
                'artist' => $artist,
                'slow' => false,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Azure TTS failed: {$response->body()}");
        }

        return $response->body();
    }
}

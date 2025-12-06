<?php

namespace App\Services\TTS\Contracts;

interface TTSDriver
{
    /**
     * Convert text to speech.
     *
     * @param string $text
     * @param string $language
     * @param string $gender
     * @param string $artist
     * @param string $format
     * @return string Raw audio data
     */
    public function speak(string $text, string $language, string $gender, string $artist, string $format = 'mp3'): string;
}

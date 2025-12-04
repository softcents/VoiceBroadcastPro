<?php

namespace Database\Factories;

use App\Models\AudioFile;
use App\Models\AudioRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioFile>
 */
class AudioFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audio_record_id' => AudioRecord::factory(),
            'name' => fake()->word() . '.mp3',
            'path' => 'audio-records/' . fake()->uuid() . '.mp3',
            'size' => fake()->numberBetween(1000, 1000000),
            'mime_type' => 'audio/mpeg',
        ];
    }
}

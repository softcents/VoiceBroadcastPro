<?php

namespace Database\Factories;

use App\Enums\AudioRecordStatus;

use App\Enums\AudioType;
use App\Models\Audio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Audio>
 */
class AudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => AudioType::TTS,
            'approval' => \App\Enums\AudioApproval::Pending,
            'message' => $this->faker->sentence(),
            'tts_artist_id' => \App\Models\TTSArtist::factory(),
        ];
    }
}

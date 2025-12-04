<?php

namespace Database\Factories;

use App\Enums\AudioRecordStatus;
use App\Enums\AudioArtist;
use App\Enums\AudioGender;
use App\Enums\AudioLanguage;
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
            'language' => AudioLanguage::BnBD,
            'gender' => AudioGender::Male,
            'artist' => AudioArtist::BnBdPradeepNeural,
        ];
    }
}

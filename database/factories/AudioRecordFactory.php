<?php

namespace Database\Factories;

use App\Enums\AudioRecordStatus;
use App\Models\AudioRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioRecord>
 */
class AudioRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->colorName(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(AudioRecordStatus::cases()),
        ];
    }
}

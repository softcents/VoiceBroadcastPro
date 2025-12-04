<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
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
            'audio_id' => \App\Models\Audio::factory(),
            'phonebook_id' => \App\Models\Phonebook::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'source' => \App\Enums\CampaignSource::Manual,
            'status' => \App\Enums\CampaignStatus::Pending,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}

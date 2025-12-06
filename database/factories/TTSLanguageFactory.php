<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TTSLanguage>
 */
class TTSLanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->languageCode(),
            'code' => $this->faker->unique()->languageCode() . '-' . $this->faker->countryCode(),
            'engine' => 'neural',
            'enabled' => true,
        ];
    }
}

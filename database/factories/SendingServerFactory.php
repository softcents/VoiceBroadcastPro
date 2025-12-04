<?php

namespace Database\Factories;

use App\Models\SendingServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SendingServer>
 */
class SendingServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'domain' => fake()->domainName(),
            'username' => fake()->username(),
            'password' => fake()->password(),
            'enabled' => fake()->boolean(),
        ];
    }
}

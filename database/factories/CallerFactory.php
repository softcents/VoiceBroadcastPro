<?php

namespace Database\Factories;

use App\Models\SendingServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Caller>
 */
class CallerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sending_server_id' => SendingServer::factory(),
            'caller_name' => fake()->name(),
            'caller_number' => fake()->e164PhoneNumber(),
            'enabled' => fake()->boolean(),
        ];
    }
}

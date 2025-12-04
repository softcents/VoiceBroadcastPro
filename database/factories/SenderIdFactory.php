<?php

namespace Database\Factories;

use App\Models\SenderId;
use App\Models\SendingServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SenderId>
 */
class SenderIdFactory extends Factory
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
            'sender_id' => fake()->unique()->bothify('??????'),
            'enabled' => fake()->boolean(),
        ];
    }
}

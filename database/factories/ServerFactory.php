<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
final class ServerFactory extends Factory
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
            'scheme' => fake()->randomElement(['http', 'https']),
            'host' => fake()->ipv4(),
            'port' => 8088,
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'enabled' => fake()->boolean(),
        ];
    }
}

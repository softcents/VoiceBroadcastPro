<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
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
            'ari_domain' => 'http://' . fake()->ipv4() . ':8088',
            'ari_username' => fake()->userName(),
            'ari_password' => fake()->password(),
            'database_host' => fake()->ipv4(),
            'database_port' => 3306,
            'database_name' => 'asteriskcdrdb',
            'database_username' => fake()->userName(),
            'database_password' => fake()->password(),
            'enabled' => fake()->boolean(),
        ];
    }
}

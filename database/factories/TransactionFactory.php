<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
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
            'type' => $this->faker->randomElement(['deposit', 'expense', 'refund']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'BDT',
            'description' => $this->faker->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}

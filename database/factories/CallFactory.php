<?php

namespace Database\Factories;

use App\Enums\CallStatus;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
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
            'campaign_id' => Campaign::factory(),
            'contact_id' => Contact::factory(),
            'phone_number' => '+88017' . fake()->numerify('#########'),
            'content' => fake()->paragraph(),
            'status' => fake()->randomElement(CallStatus::cases()),
        ];
    }
}

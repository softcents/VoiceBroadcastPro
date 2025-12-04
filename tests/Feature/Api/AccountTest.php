<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_account_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/account');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/account', [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'newemail@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->putJson('/api/account/password', [
            'current_password' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_can_get_balance(): void
    {
        $user = User::factory()->create([
            'balance' => 10,
        ]);

        $response = $this->actingAs($user)->getJson('/api/account/balance');

        $response->assertOk()
            ->assertJsonPath('data.balance', 10);
    }
}

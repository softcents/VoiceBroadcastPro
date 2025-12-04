<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_panel()
    {
        $admin = User::factory()->create([
            'type' => UserType::Admin,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'type' => UserType::User,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_user_can_access_user_panel()
    {
        $user = User::factory()->create([
            'type' => UserType::User,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertSuccessful();
    }

    public function test_admin_cannot_access_user_panel()
    {
        $admin = User::factory()->create([
            'type' => UserType::Admin,
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertForbidden();
    }
}

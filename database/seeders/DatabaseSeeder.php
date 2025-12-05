<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'type' => UserType::Admin,
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@mail.com',
            'type' => UserType::User,
        ]);

        $this->call([
            SendingServerSeeder::class,
            DemoSeeder::class,
            UserTwoDataSeeder::class,
        ]);
    }
}

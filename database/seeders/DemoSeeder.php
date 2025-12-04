<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\AudioFile;
use App\Models\AudioRecord;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo user
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'type' => UserType::User,
        ]);

        // Create some contact groups for the user
        $groups = ContactGroup::factory(3)
            ->for($user)
            ->create();

        // Add contacts to each group
        foreach ($groups as $group) {
            Contact::factory(5)
                ->for($group)
                ->create();
        }

        // Create some audio records with files for the user
        AudioRecord::factory(5)
            ->for($user)
            ->has(AudioFile::factory()->count(2), 'audioFiles')
            ->create();
    }
}

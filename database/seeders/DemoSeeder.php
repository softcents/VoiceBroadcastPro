<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\AudioFile;
use App\Models\AudioRecord;
use App\Models\Contact;
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



        // Create some phonebooks for the user
        $phonebooks = \App\Models\Phonebook::factory(3)
            ->for($user)
            ->create();

        // Add contacts to each phonebook
        foreach ($phonebooks as $phonebook) {
            Contact::factory(5)
                ->for($phonebook)
                ->create();
        }

        // Create some audio records with files for the user
        AudioRecord::factory(5)
            ->for($user)
            ->has(AudioFile::factory()->count(2), 'audioFiles')
            ->create();
    }
}

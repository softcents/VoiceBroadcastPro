<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::find(2);

        if (!$user) {
            return;
        }

        // Phonebooks and Contacts
        $phonebooks = \App\Models\Phonebook::factory(10)
            ->for($user)
            ->create();

        foreach ($phonebooks as $phonebook) {
            \App\Models\Contact::factory(50)
                ->for($phonebook)
                ->create();
        }

        // Audios
        $audios = \App\Models\Audio::factory(20)
            ->for($user)
            ->create();

        // Campaigns and Calls
        // Campaigns (Source: Phonebook)
        $campaignsPhonebook = \App\Models\Campaign::factory(25)
            ->for($user)
            ->recycle($audios)
            ->recycle($phonebooks)
            ->create([
                'source' => \App\Enums\CampaignSource::Phonebook->value,
            ]);

        // Campaigns (Source: Manual)
        $campaignsManual = \App\Models\Campaign::factory(25)
            ->for($user)
            ->recycle($audios)
            ->create([
                'source' => \App\Enums\CampaignSource::Manual->value,
                'phonebook_id' => null,
            ]);

        $campaigns = $campaignsPhonebook->merge($campaignsManual);

        foreach ($campaigns as $campaign) {
            \App\Models\Call::factory(rand(20, 100))
                ->for($user)
                ->for($campaign)
                ->create();
        }

        // Deposits
        \App\Models\Deposit::factory(20)
            ->for($user)
            ->create();

        // Transactions
        \App\Models\Transaction::factory(20)
            ->for($user)
            ->create();
    }
}

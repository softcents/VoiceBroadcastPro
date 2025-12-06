<?php

namespace Database\Seeders;

use App\Enums\CampaignSource;
use App\Models\Audio;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Deposit;
use App\Models\Phonebook;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(2);

        if (!$user) {
            return;
        }

        // Phonebooks and Contacts
        $phonebooks = Phonebook::factory(10)
            ->for($user)
            ->create();

        foreach ($phonebooks as $phonebook) {
            Contact::factory(50)
                ->for($phonebook)
                ->create();
        }

        // Audios
        $audios = Audio::factory(20)
            ->for($user)
            ->create();

        // Campaigns and Calls
        // Campaigns (Source: Phonebook)
        $campaignsPhonebook = Campaign::factory(25)
            ->for($user)
            ->recycle($audios)
            ->recycle($phonebooks)
            ->create([
                'source' => CampaignSource::Phonebook->value,
            ]);

        // Campaigns (Source: Manual)
        $campaignsManual = Campaign::factory(25)
            ->for($user)
            ->recycle($audios)
            ->create([
                'source' => CampaignSource::Manual->value,
                'phonebook_id' => null,
            ]);

        $campaigns = $campaignsPhonebook->merge($campaignsManual);

        foreach ($campaigns as $campaign) {
            Call::factory(rand(20, 100))
                ->for($user)
                ->for($campaign)
                ->create();
        }

        // Deposits
        Deposit::factory(20)
            ->for($user)
            ->create();

        // Transactions
        Transaction::factory(20)
            ->for($user)
            ->create();
    }
}

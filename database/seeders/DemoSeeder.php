<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AudioApproval;
use App\Enums\AudioType;
use App\Jobs\ConvertAudio;
use App\Jobs\GenerateAudio;
use App\Models\Audio;
use App\Models\Phonebook;
use App\Models\TTSArtist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Bus;

final class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(2);

        $phonebook = Phonebook::factory()->create();

        $contacts = [
            [
                'first_name' => 'Bishwajit',
                'last_name' => 'Grameenphone',
                'phone_number' => '+8801322635808',
            ],
            [
                'first_name' => 'Bishwajit',
                'last_name' => 'Teletalk',
                'phone_number' => '+8801712345678',
            ]
        ];

        foreach ($contacts as $contact) {
            $phonebook->contacts()->create($contact);
        }

        Audio::factory(3)
            ->afterCreating(function (Audio $audio) {
                $audio->update([
                    'approval' => AudioApproval::Approved,
                ]);

                Bus::chain([
                    new GenerateAudio($audio->id),
                    new ConvertAudio($audio->id),
                ])->dispatch();
            })
            ->create([
                'user_id' => $user->id,
                'approval' => AudioApproval::Pending,
                'type' => AudioType::TTS,
                'tts_artist_id' => TTSArtist::whereCode('bn-BD-PradeepNeural')
                    ->whereHas('ttsLanguage', function ($query) {
                        $query->where('engine', 'frolax');
                    })->first()->id,
            ]);
    }
}

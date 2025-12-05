<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AriTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ari-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withBasicAuth('softcents', 'password')
            ->post("http://160.191.163.122:8088/ari/channels", [
                'endpoint' => "Local/s@from-http/n",
                "app" => "originate",
                "variables" => [
                    "TYPE" => "AUDIO",
                    "RECIPIENT" => "100",
                    "TRUNK" => "100",
//                    "RECIPIENT" => "+8801322635808",
//                    "TRUNK" => "09617510201",
                    "IDENTIFIER" => "10",
                    "AUDIO_URL" => "https://frolax.agency/output.alaw",
                    "WEBHOOK_URL" => "https://voice.frolax.net/webhooks/asterisk"
                ]
            ]);

        $this->info($response->body());
    }
}

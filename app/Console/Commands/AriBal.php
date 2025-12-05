<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AriBal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ari-bal';

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
                'context' => "from-http",
                'extension' => "s",
                'priority' => 1,
                "variables" => [
                    "TYPE" => "OTP",
                    "RECIPIENT" => "+8801322635808",
                    "TRUNK" => "09617510201",
                    "IDENTIFIER" => "10",
                    "AUDIO_URL" => "http://160.191.163.122/code.alaw",
                    "CODE" => "1234",
                    "WEBHOOK_URL" => "https://voice.frolax.net/webhooks/asterisk"
                ]
            ]);

        $this->info($response->body());
    }
}

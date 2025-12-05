<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class AriListener extends Command
{
    protected $signature = 'app:ari-listener';
    protected $description = 'Listen to ARI events and handle them accordingly';

    public function handle()
    {
        $wsUrl = "ws://160.191.163.122:8088/ari/events?api_key=softcents:password&app=originate";

        $this->info('Connecting to ARI WebSocket...');

        $client = new Client($wsUrl, ['timeout' => 50000000000, 'reconnect' => true]);

        $this->info('Connected. Listening for events...');

        while (true) {
            $msg = $client->receive();
            if(!$msg) continue;

            $event = json_decode($msg, true);
            if (!is_array($event) || empty($event['type'])) continue;

            $type = $event['type'];

            ray($event)->label($type);

//            switch ($type) {
//                case 'StasisStart':
//                    $this->info("StasisStart event received for channel: " . $event['channel']['id']);
//                    // Handle StasisStart event
//                    break;
//                case 'StasisEnd':
//                    $this->info("StasisEnd event received for channel: " . $event['channel']['id']);
//                    // Handle StasisEnd event
//                    break;
//
//                default:
//                    Log::info("Unknown event type: " . $type, $event);
//                    $this->info("Unhandled event type: " . $type);
//                    break;
//            }
        }
    }
}

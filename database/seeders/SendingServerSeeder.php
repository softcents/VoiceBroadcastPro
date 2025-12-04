<?php

namespace Database\Seeders;

use App\Models\SendingServer;
use Illuminate\Database\Seeder;

class SendingServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servers = [
            [
                'name' => 'Hostomega BDIX',
                'domain' => 'http://160.191.163.122:8080',
                'username' => 'softcents',
                'password' => 'password',
                'enabled' => true,
            ],
        ];

        foreach ($servers as $server) {
            SendingServer::create($server);
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

final class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servers = [
            [
                'name' => 'Hostomega BDIX',
                'scheme' => 'http',
                'host' => '160.191.163.122',
                'port' => '8088',
                'username' => 'softcents',
                'password' => 'password',
                'enabled' => true,
            ],
        ];

        foreach ($servers as $server) {
            Server::create($server);
        }
    }
}

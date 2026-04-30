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
                'name' => 'Dedicated VPS',
                'ari_host' => '103.191.241.11',
                'ari_scheme' => 'http',
                'ari_port' => 8088,
                'ari_username' => 'softcents',
                'ari_password' => 'softcents',

                'database_host' => '103.191.241.11',
                'database_port' => 3306,
                'database_username' => 'trigger_one',
                'database_password' => 'Pass123',
                'enabled' => true,
            ],
        ];

        foreach ($servers as $server) {
            Server::create($server);
        }
    }
}

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
                'host' => '160.191.163.122',
                'port' => '8088',
                'username' => 'softcents',
                'password' => 'password',
                'database_host' => '160.191.163.122',
                'database_port' => 3306,
                'database_name' => 'asteriskcdrdb',
                'database_username' => 'bishwajit',
                'database_password' => 'password',
                'enabled' => true,
            ],
        ];

        foreach ($servers as $server) {
            Server::create($server);
        }
    }
}

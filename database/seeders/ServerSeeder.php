<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servers = [
            [
                'name' => 'Hostomega BDIX',
                'ari_domain' => 'http://160.191.163.122:8088',
                'ari_username' => 'softcents',
                'ari_password' => 'password',
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

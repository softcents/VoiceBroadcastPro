<?php

namespace Database\Seeders;

use App\Models\Caller;
use Illuminate\Database\Seeder;

class CallerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $callers = [
            [
                'server_id' => 1,
                'caller_name' => 'SoftCents',
                'caller_number' => '09617510201',
                'enabled' => true,
            ]
        ];

        foreach ($callers as $caller) {
            Caller::create($caller);
        }
    }
}

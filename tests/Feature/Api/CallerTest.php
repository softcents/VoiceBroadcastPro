<?php

namespace Tests\Feature\Api;

use App\Models\Caller;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

test('can list callers', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $callers = Caller::factory(3)
        ->for($user->sendingServers()->first() ?? \App\Models\SendingServer::factory()->create())
        ->create(['enabled' => true]);
    
    // Attach callers to user
    $user->callers()->attach($callers);

    $response = getJson('/api/callers');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

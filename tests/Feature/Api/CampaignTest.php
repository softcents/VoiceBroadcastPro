<?php

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaign;
use App\Models\Audio;
use App\Models\Campaign;
use App\Models\Phonebook;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

test('user can list their campaigns', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Campaign::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->getJson(route('campaigns.index'));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('user can create a campaign', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $audio = Audio::factory()->create(['user_id' => $user->id]);
    $phonebook = Phonebook::factory()->create(['user_id' => $user->id]);

    $data = [
        'title' => 'Test Campaign',
        'description' => 'Test Description',
        'audio_id' => $audio->id,
        'phonebook_id' => $phonebook->id,
        'source' => CampaignSource::Phonebook->value,
        'status' => CampaignStatus::Pending->value,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
    ];

    Bus::fake();

    $response = $this->postJson(route('campaigns.store'), $data);

    $response->assertCreated()
        ->assertJsonFragment(['title' => 'Test Campaign']);

    $this->assertDatabaseHas('campaigns', ['title' => 'Test Campaign']);

    Bus::assertDispatched(ProcessCampaign::class);
});

test('user can view a specific campaign', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $response = $this->getJson(route('campaigns.show', $campaign));

    $response->assertOk()
        ->assertJsonFragment(['id' => $campaign->id]);
});

test('user cannot view others campaign', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($user);

    $campaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson(route('campaigns.show', $campaign));

    $response->assertForbidden();
});

test('user can update a campaign', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $data = ['title' => 'Updated Campaign'];

    $response = $this->putJson(route('campaigns.update', $campaign), $data);

    $response->assertOk()
        ->assertJsonFragment(['title' => 'Updated Campaign']);

    $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'title' => 'Updated Campaign']);
});

test('user can delete a campaign', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $response = $this->deleteJson(route('campaigns.destroy', $campaign));

    $response->assertNoContent();

    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});

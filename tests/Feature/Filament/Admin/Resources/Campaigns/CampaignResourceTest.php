<?php

use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use App\Enums\UserType;
use function Pest\Livewire\livewire;

it('can list campaigns', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $campaigns = Campaign::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(CampaignResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee($campaigns->first()->title);
});

it('can render create campaign page', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);

    $this->actingAs($admin)
        ->get(CampaignResource::getUrl('create'))
        ->assertSuccessful();
});

it('can render edit campaign page', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $campaign = Campaign::factory()->create();

    $this->actingAs($admin)
        ->get(CampaignResource::getUrl('edit', ['record' => $campaign]))
        ->assertSuccessful()
        ->assertSee($campaign->title);
});

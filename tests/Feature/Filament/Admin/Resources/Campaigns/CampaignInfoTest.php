<?php

use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Models\Call;
use App\Models\User;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Campaigns\Widgets\CampaignStatsWidget;
use App\Filament\Admin\Resources\Campaigns\Widgets\CampaignChartWidget;

it('can view campaign info page with widgets', function () {
    $admin = User::factory()->create(['type' => UserType::Admin]);
    $campaign = Campaign::factory()->create();
    $calls = Call::factory()->count(5)->create(['campaign_id' => $campaign->id]);

    $this->actingAs($admin)
        ->get(CampaignResource::getUrl('view', ['record' => $campaign]))
        ->assertSuccessful()
        ->assertSeeLivewire(CampaignStatsWidget::class)
        ->assertSeeLivewire(CampaignChartWidget::class);
});

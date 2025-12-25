<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Pages;

use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\ListRecords;

final class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;
}

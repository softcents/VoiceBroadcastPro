<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Pages;

use App\Filament\User\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->campaigns()->create($data);
    }
}

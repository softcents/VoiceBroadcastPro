<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calling\Campaigns\Pages;

use App\Filament\User\Resources\Calling\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(static function () use ($data) {
            return auth()->user()->campaigns()->create($data);
        });
    }
}

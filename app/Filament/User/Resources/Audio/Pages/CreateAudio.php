<?php

namespace App\Filament\User\Resources\Audio\Pages;

use App\Enums\AudioConversionStatus;
use App\Enums\AudioTTSStatus;
use App\Enums\AudioType;
use App\Filament\User\Resources\Audio\AudioResource;
use App\Jobs\ConvertAudio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\Audio;

class CreateAudio extends CreateRecord
{
    protected static string $resource = AudioResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return auth()->user()->audio()->create($data);
    }

    protected function afterCreate(): void
    {
        /* @var Audio $audio */
        $audio = $this->record;

        if ($audio->type === AudioType::Upload) {
            // Dispatch job to convert audio for Asterisk
            ConvertAudio::dispatch($audio->id);
        }
    }
}

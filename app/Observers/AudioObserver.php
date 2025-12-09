<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AudioApproval;
use App\Enums\AudioTTSStatus;
use App\Enums\AudioType;
use App\Filament\Admin\Resources\Audio\AudioResource;
use App\Jobs\ConvertAudio;
use App\Jobs\GenerateAudio;
use App\Models\Audio;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Bus;

final class AudioObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Audio "created" event.
     */
    public function created(Audio $audio): void
    {
        $admins = User::admin()->get();
        Notification::make()
            ->title('Audio')
            ->body("New audio has been created")
            ->success()
            ->actions([
                Action::make('view')
                    ->label('View Audio')
                    ->url(AudioResource::getUrl('view', ['record' => $audio->id], panel: 'admin')),
            ])
            ->sendToDatabase($admins);
    }

    /**
     * Handle the Audio "updated" event.
     */
    public function updated(Audio $audio): void
    {
        if ($audio->wasChanged('approval') && $audio->approval === AudioApproval::Approved) {

            // If the audio is of type TTS and not yet completed, dispatch jobs to generate and convert audio
            if ($audio->type === AudioType::TTS && $audio->tts_status !== AudioTTSStatus::Completed) {
                Bus::chain([
                    new GenerateAudio($audio->id),
                    new ConvertAudio($audio->id),
                ])->dispatch();
            }
        }
    }

    /**
     * Handle the Audio "deleted" event.
     */
    public function deleted(Audio $audio): void
    {
        //
    }
}

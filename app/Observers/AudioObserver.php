<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AudioApproval;
use App\Enums\AudioTTSStatus;
use App\Filament\Admin\Resources\Audio\AudioResource as AdminAudioResource;
use App\Filament\User\Resources\Audio\AudioResource as UserAudioResource;
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
        //        $admins = User::admin()->get();
        //        Notification::make()
        //            ->title('Audio')
        //            ->body("New audio has been created")
        //            ->success()
        //            ->actions([
        //                Action::make('view')
        //                    ->label('View Audio')
        //                    ->url(AdminAudioResource::getUrl('view', ['record' => $audio->id], panel: 'admin')),
        //            ])
        //            ->sendToDatabase($admins);
    }

    /**
     * Handle the Audio "updated" event.
     */
    public function updated(Audio $audio): void
    {
        if ($audio->wasChanged('approval') && $audio->approval === AudioApproval::Approved) {
            if ($audio->tts_status !== AudioTTSStatus::Completed) {
                Bus::chain([
                    new GenerateAudio($audio->id),
                    new ConvertAudio($audio->id),
                ])->dispatch();
            }
        }

        //        if ($audio->wasChanged('approval')) {
        //            $user = $audio->user;
        //            $approval = $audio->approval->value;
        //
        //            Notification::make()
        //                ->title('Audio Approval Updated')
        //                ->body("Your audio titled <strong>$audio->title</strong> has been <strong>$approval</strong>.")
        //                ->success()
        //                ->actions([
        //                    Action::make('view')
        //                        ->label('View Audio')
        //                        ->url(UserAudioResource::getUrl('view', ['record' => $audio->id], panel: 'user')),
        //                ])
        //                ->sendToDatabase($user);
        //        }
        //
        //        if ($audio->wasChanged('tts_status')) {
        //            $user = $audio->user;
        //            $ttsStatus = $audio->tts_status->value;
        //
        //            Notification::make()
        //                ->title('Audio TTS Status Updated')
        //                ->body("The TTS generation for your audio titled '{$audio->title}' is now '{$ttsStatus}'.")
        //                ->success()
        //                ->sendToDatabase($user);
        //        }
        //
        //        if ($audio->wasChanged('conversion_status')) {
        //            $user = $audio->user;
        //            $conversionStatus = $audio->conversion_status->value;
        //
        //            Notification::make()
        //                ->title('Audio Conversion Status Updated')
        //                ->body("The conversion status for your audio titled '{$audio->title}' is now '{$conversionStatus}'.")
        //                ->success()
        //                ->sendToDatabase($user);
        //        }
    }

    /**
     * Handle the Audio "deleted" event.
     */
    public function deleted(Audio $audio): void
    {
        //
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;
use LaraZeus\Tabler\Tabler;

enum AudioType: string implements HasLabel, HasColor, HasIcon
{
    case TTS = 'tts';
    case Upload = 'upload';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TTS => 'Text to Speech',
            self::Upload => 'Upload Audio',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TTS => 'info',
            self::Upload => 'primary',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::TTS => Tabler::Robot,
            self::Upload => Tabler::Microphone,
        };
    }

    public static function availableOptions(UserAudioType $audioType): Collection
    {
        return (match ($audioType) {
            UserAudioType::TTS => collect([self::TTS]),
            UserAudioType::Upload => collect([self::Upload]),
            UserAudioType::Both => collect([self::TTS, self::Upload]),
        })->mapWithKeys(fn ($item) => [$item->value => $item->getLabel()]);
    }
}

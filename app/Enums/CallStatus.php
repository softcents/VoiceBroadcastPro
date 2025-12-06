<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum CallStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Initiated = 'initiated';
    case Ringing = 'ringing';
    case Answered = 'answered';
    case Busy = 'busy';
    case NotAnswered = 'not_answered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NotAnswered => 'Not Answered',
            default => str($this->name)->headline(),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Answered => 'success',
            self::Failed, self::Busy, self::NotAnswered => 'danger',
            self::Ringing => 'warning',
            self::Initiated => 'info',
            self::Pending, self::Cancelled => 'gray',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending => Tabler::Clock,
            self::Initiated => Tabler::PlayerPlay,
            self::Ringing => Tabler::PhoneCalling,
            self::Answered => Tabler::PhoneCheck,
            self::Busy => Tabler::PhoneX,
            self::NotAnswered => Tabler::PhoneOff,
            self::Failed => Tabler::CircleX,
            self::Cancelled => Tabler::Ban,
        };
    }
}

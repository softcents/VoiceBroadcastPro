<?php

declare(strict_types=1);

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
    case Completed = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NotAnswered => 'Not Answered',
            default => str($this->name)->headline()->value(),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Completed,
            self::Answered => 'success',
            self::Failed,
            self::Busy,
            self::NotAnswered => 'danger',
            self::Ringing, self::Initiated => 'info',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending => Tabler::Clock,
            self::Initiated => Tabler::Run,
            self::Ringing => Tabler::PhoneCalling,
            self::Completed,
            self::Answered => Tabler::PhoneCheck,
            self::Busy => Tabler::PhoneX,
            self::NotAnswered => Tabler::PhoneOff,
            self::Failed => Tabler::CircleX,
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isRefundable(): bool
    {
        return in_array($this, [
            self::Failed,
            self::Busy,
            self::NotAnswered,
        ], true);
    }
}

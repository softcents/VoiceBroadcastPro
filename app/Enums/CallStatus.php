<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CallStatus: string implements HasLabel
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
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum DepositStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return str($this->name)->headline();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Pending => 'warning',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending => Tabler::Clock,
            self::Completed => Tabler::CircleCheck,
            self::Failed => Tabler::CircleX,
            self::Cancelled => Tabler::Ban,
        };
    }
}

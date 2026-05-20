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
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): ?string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending    => 'gray',
            self::Processing => 'info',
            self::Completed  => 'success',
            self::Failed     => 'danger',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending    => Tabler::Clock,
            self::Processing => Tabler::PhoneCalling,
            self::Completed  => Tabler::PhoneCheck,
            self::Failed     => Tabler::CircleX,
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isRefundable(): bool
    {
        return $this === self::Failed;
    }
}

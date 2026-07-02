<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum CampaignStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Processing = 'processing';
    case Failed = 'failed';
    case Finished = 'finished';
    case Paused = 'paused';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Cancelled => 'warning',
            self::Processing => 'info',
            self::Failed => 'danger',
            self::Finished => 'success',
            self::Paused => 'info',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending => Tabler::Clock,
            self::Cancelled => Tabler::Ban,
            self::Processing => Tabler::Loader,
            self::Failed => Tabler::CircleX,
            self::Finished => Tabler::CircleCheck,
            self::Paused => Tabler::PlayerPause,
        };
    }
}

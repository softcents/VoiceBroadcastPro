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
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Processing => 'warning',
            self::Pending, self::Cancelled => 'gray',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Pending => Tabler::Clock,
            self::Processing => Tabler::Loader,
            self::Completed => Tabler::CircleCheck,
            self::Failed => Tabler::CircleX,
            self::Cancelled => Tabler::Ban,
        };
    }
}

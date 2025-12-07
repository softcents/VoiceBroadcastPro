<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum UserType: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::User => 'info',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Admin => Tabler::ShieldCheck,
            self::User => Tabler::User,
        };
    }
}

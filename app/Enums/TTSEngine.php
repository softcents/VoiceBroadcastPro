<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum TTSEngine: string implements HasColor, HasIcon, HasLabel
{
    case Azure = 'azure';
    case Google = 'google';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Azure => 'info',
            self::Google => 'warning',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Azure => Tabler::BrandAzure,
            self::Google => Tabler::BrandGoogle,
        };
    }
}

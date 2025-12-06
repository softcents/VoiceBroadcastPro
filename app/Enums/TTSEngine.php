<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum TTSEngine: string implements HasLabel, HasColor, HasIcon
{
    case Azure = 'azure';
    case Google = 'google';

    public function getLabel(): ?string
    {
        return str($this->name)->headline();
    }

    public function getColor(): string|array|null
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

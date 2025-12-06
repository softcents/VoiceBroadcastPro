<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum CampaignSource: string implements HasLabel, HasColor, HasIcon
{
    case Manual = 'manual';
    case Import = 'import';
    case Phonebook = 'phonebook';

    public function getLabel(): ?string
    {
        return str($this->name)->headline();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Manual => 'info',
            self::Import => 'success',
            self::Phonebook => 'primary',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Manual => Tabler::Click,
            self::Import => Tabler::FileUpload,
            self::Phonebook => Tabler::AddressBook,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use LaraZeus\Tabler\Tabler;

enum TransactionType: string implements HasColor, HasIcon, HasLabel
{
    case Deposit = 'deposit';
    case Expense = 'expense';
    case Refund = 'refund';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Deposit => 'success',
            self::Expense => 'danger',
            self::Refund => 'info',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Deposit => Tabler::ArrowUp,
            self::Expense => Tabler::ArrowDown,
            self::Refund => Tabler::ArrowBackUp,
        };
    }
}

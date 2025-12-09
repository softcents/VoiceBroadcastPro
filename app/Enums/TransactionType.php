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
    case Call = 'expense';

    public function getLabel(): string
    {
        return str($this->name)->headline()->value();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Deposit => 'success',
            self::Call => 'danger',
        };
    }

    public function getIcon(): Tabler
    {
        return match ($this) {
            self::Deposit => Tabler::CreditCardPay,
            self::Call => Tabler::PhoneCall,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Customers\Actions;

use LaraZeus\Tabler\Tabler;
use STS\FilamentImpersonate\Actions\Impersonate;

final class ImpersonateAction
{
    public static function make()
    {
        return Impersonate::make()
            ->icon(Tabler::Login2)
            ->label('Login as User');
    }
}

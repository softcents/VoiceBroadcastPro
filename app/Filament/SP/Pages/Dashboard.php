<?php

declare(strict_types=1);

namespace App\Filament\SP\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    public function getTitle(): string
    {
        return 'SP Dashboard';
    }
}

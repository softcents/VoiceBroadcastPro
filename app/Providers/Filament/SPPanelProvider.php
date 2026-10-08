<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\SP\Pages\Dashboard;
use App\Filament\SP\Resources\Customers\CustomerResource;
use App\Http\Middleware\SPMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class SPPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sp')
            ->path('sp')
            ->login()
            ->profile()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->brandLogoHeight('45px')
            ->brandLogo(url('logo-dark.svg'))
            ->darkModeBrandLogo(url('logo-white.svg'))
            ->favicon(url('favicon.png'))
            ->colors(['primary' => Color::Amber])
            ->pages([Dashboard::class])
            ->resources([CustomerResource::class])
            ->navigationGroups([
                NavigationGroup::make()->label('SP')->collapsible(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SPMiddleware::class,
            ])
            ->viteTheme('resources/css/app.css')
            ->spa()
            ->unsavedChangesAlerts();
    }
}

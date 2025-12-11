<?php

declare(strict_types=1);

namespace App\Filament\User\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use LaraZeus\Tabler\Tabler;

final class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Account Balance', Number::currency(auth()->user()->balance, 'BDT'))
                ->description('Current balance in your account')
                ->icon(Tabler::PigMoney),

            // Estimated Remaining Calls
            Stat::make('Remaining Calls', (string) (auth()->user()->pulse_rate > 0 ? floor(auth()->user()->balance / auth()->user()->pulse_rate) : 0))
                ->description('Estimated number of calls')
                ->icon(Tabler::PhoneCalling),

            // Pulse Rate
            Stat::make('Pulse Rate', Number::currency(auth()->user()->pulse_rate, 'BDT'))
                ->description('Cost per pulse')
                ->icon(Tabler::HeartRateMonitor),

            Stat::make('Pulse Duration', auth()->user()->pulse_duration . ' seconds')
                ->description('Duration of one pulse')
                ->icon(Tabler::Clock),
        ];
    }
}

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
        $user = auth()->user();

        $balance = $user->balance;
        $pulseRate = $user->pulse_rate;
        $pulseDuration = $user->pulse_duration;

        $remainingCalls = $pulseRate > 0 ? floor($balance / $pulseRate) : 0;

        return [
            Stat::make('Account Balance', Number::currency($balance, 'BDT'))
                ->description('Current balance in your account')
                ->icon(Tabler::PigMoney),

            Stat::make('Remaining Calls', Number::abbreviate($remainingCalls, 0, 2))
                ->description('Estimated number of calls')
                ->icon(Tabler::PhoneCalling),

            Stat::make('Pulse Rate', Number::currency($pulseRate, 'BDT', precision: 6))
                ->description('Cost per pulse')
                ->icon(Tabler::HeartRateMonitor),

            Stat::make('Pulse Duration', $pulseDuration.' seconds')
                ->description('Duration of one pulse')
                ->icon(Tabler::Clock),
        ];
    }
}

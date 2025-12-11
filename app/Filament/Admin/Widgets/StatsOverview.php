<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Enums\UserType;
use App\Models\Call;
use App\Models\Campaign;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use LaraZeus\Tabler\Tabler;

final class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isLazy = true;

    public function getColumns(): int|array
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::whereType(UserType::User)->count())
                ->description('Active users on the platform')
                ->icon(Tabler::Users),

            Stat::make('Total Calls (Today)', Call::whereDate('created_at', now())->count())
                ->description('Calls made today')
                ->icon(Tabler::PhoneCalling),

            Stat::make('Revenue (Today)', function () {
                $cost = Call::whereDate('created_at', now())
                    ->whereStatus(CallStatus::Completed)
                    ->sum('cost');

                return Number::currency((float) $cost, 'BDT');
            })
                ->description('Total revenue generated today')
                ->icon(Tabler::Businessplan),

            Stat::make('Total User Balance', function () {
                $balance = User::whereType(UserType::User)
                    ->sum('balance');

                return Number::currency((float) $balance, 'BDT');
            })
                ->description('Total wallet balance of all users')
                ->icon(Tabler::Wallet),

            Stat::make('Active Campaigns', Campaign::whereStatus(CampaignStatus::Processing)->count())
                ->description('Campaigns currently processing')
                ->icon(Tabler::Loader),

            Stat::make('Pending Campaigns', Campaign::whereStatus(CampaignStatus::Pending)->count())
                ->description('Campaigns waiting to start')
                ->icon(Tabler::Clock),

            Stat::make('Completed (Today)', Campaign::whereStatus(CampaignStatus::Completed)->whereDate('updated_at', today())->count())
                ->description('Campaigns completed today')
                ->icon(Tabler::CircleCheck),

            Stat::make('Total Campaigns', Campaign::count())
                ->description('All time total campaigns')
                ->icon(Tabler::Speakerphone),
        ];
    }
}

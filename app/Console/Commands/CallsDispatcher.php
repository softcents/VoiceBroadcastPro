<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\CampaignStatus;
use App\Jobs\ProcessMarketingCall;
use App\Jobs\ProcessOtpCall;
use App\Models\Call;
use App\Models\Caller;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CallsDispatcher extends Command
{
    protected $signature = 'calls:dispatch';

    protected $description = 'Dispatch calls based on callers concurrency limits';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        $this->components->info('Dispatching calls based on Caller ID concurrency limits');

        $callers = Caller::where('enabled', true)
            ->with('server')
            ->get();

        if ($callers->isEmpty()) {
            $this->components->warn('No enabled callers found');

            return self::SUCCESS;
        }

        $totalDispatched = 0;

        foreach ($callers as $caller) {
            if (!$caller->server || !$caller->server->enabled) {
                continue;
            }

            $this->components->twoColumnDetail('Processing Caller', "{$caller->caller_name} ({$caller->caller_number})");

            // 1. Calculate Active Calls for this Caller
            $activeCallsCount = Call::active()
                ->whereCallerId($caller->id)
                ->count();

            $limit = $caller->max_concurrency;
            $availableSlots = $limit > 0 ? max(0, $limit - $activeCallsCount) : 1000;

            $this->components->twoColumnDetail('Active Calls', (string)$activeCallsCount);
            $this->components->twoColumnDetail('Slots Available', $limit > 0 ? (string)$availableSlots : 'Unlimited');

            if ($availableSlots <= 0) {
                $this->components->warn('Caller limit reached. Skipping...');

                continue;
            }

            // 2. Fetch Pending Calls for this Caller
            // Logic: Standalone calls (scheduled or now) OR Campaign calls (only if campaign is Processing)
            $pendingCalls = Call::pending()
                ->whereCallerId($caller->id)
                ->where(function (Builder $query) {
                    $query->where(function ($q) {
                            $q->whereNull('campaign_id')
                                ->where(function ($sq) {
                                    $sq->whereNull('scheduled_at')
                                        ->orWherePast('scheduled_at');
                                });
                        })
                        ->orWhere(function ($q) {
                            $q->whereNotNull('campaign_id')
                                ->whereHas('campaign', fn($cq) => $cq->where('status', CampaignStatus::Processing));
                        });
                })
                ->oldest()
                ->limit($availableSlots)
                ->get();

            if ($pendingCalls->isEmpty()) {
                $this->components->info('No pending calls for this caller.');

                continue;
            }

            // 3. Dispatch Jobs
            DB::transaction(function () use ($pendingCalls, $caller, &$totalDispatched) {
                $callIds = $pendingCalls->pluck('id');

                DB::table('calls')
                    ->whereIn('id', $callIds)
                    ->update([
                        'status' => CallStatus::Initiated,
                        'initiated_at' => now(),
                    ]);

                $jobs = $pendingCalls->map(function ($call) {
                    return match ($call->type) {
                        CallType::OTP => new ProcessOtpCall($call->id)->onQueue('otp'),
                        default => new ProcessMarketingCall($call->id)->onQueue('marketing'),
                    };
                });

                $batch = Bus::batch($jobs->toArray())
                    ->name('Dispatcher - ' . $caller->name . ' - ' . now()->format('H:i:s'))
                    ->allowFailures()
                    ->dispatch();

                $totalDispatched += $pendingCalls->count();

                Log::info('Dispatcher: Calls batch dispatched', [
                    'caller' => $caller->name,
                    'batch_id' => $batch->id,
                    'call_count' => $pendingCalls->count(),
                ]);
            });

            $this->components->task("Dispatched {$pendingCalls->count()} calls", fn() => true);
        }

        if ($totalDispatched > 0) {
            $this->newLine();
            $this->components->info("Dispatch complete. Total calls dispatched: {$totalDispatched}");
        } else {
            $this->components->warn('No calls were dispatched in this cycle.');
        }

        return self::SUCCESS;
    }
}

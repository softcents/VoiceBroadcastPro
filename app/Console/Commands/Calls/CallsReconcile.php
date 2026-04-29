<?php

declare(strict_types=1);

namespace App\Console\Commands\Calls;

use App\Enums\CallStatus;
use App\Models\Asterisk\Cdr;
use App\Models\Asterisk\Cel;
use App\Models\Call;
use App\Models\Server;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CallsReconcile extends Command
{
    // Start consulting Asterisk after this long in a non-terminal state
    private const int RECONCILE_INITIATED_AFTER_MINUTES = 2;

    private const int RECONCILE_RINGING_AFTER_MINUTES = 2;

    private const int RECONCILE_ANSWERED_AFTER_MINUTES = 30;

    // Give up and mark Failed after this long if Asterisk has nothing useful
    private const int FAIL_INITIATED_AFTER_MINUTES = 5;

    private const int FAIL_RINGING_AFTER_MINUTES = 5;

    private const int FAIL_ANSWERED_AFTER_MINUTES = 60;

    protected $signature = 'calls:reconcile';

    protected $description = 'Reconcile stale active calls against Asterisk CEL/CDR; fail calls that cannot be reconciled.';

    public function handle(): int
    {
        $stale = $this->fetchStaleCalls();

        if ($stale->isEmpty()) {
            $this->components->info('No stale calls to reconcile.');

            return self::SUCCESS;
        }

        $reconciled = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($stale->groupBy(fn (Call $c) => $c->caller?->server?->id) as $callsForServer) {
            $server = $callsForServer->first()->caller?->server;

            if (! $server || ! $server->database_host) {
                foreach ($callsForServer as $call) {
                    if ($this->isPastHardFail($call)) {
                        $this->markFailed($call, 'no server database credentials');
                        $failed++;
                    } else {
                        $skipped++;
                    }
                }

                continue;
            }

            $this->activateAsteriskConnection($server);

            foreach ($callsForServer as $call) {
                try {
                    match ($this->reconcileCall($call)) {
                        'reconciled' => $reconciled++,
                        'failed' => $failed++,
                        default => $skipped++,
                    };
                } catch (Throwable $e) {
                    $skipped++;
                    Log::error('CallsReconcile: error reconciling call', [
                        'call_id' => $call->id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->components->twoColumnDetail('Stale calls scanned', (string) $stale->count());
        $this->components->twoColumnDetail('Reconciled from Asterisk', (string) $reconciled);
        $this->components->twoColumnDetail('Marked failed', (string) $failed);
        $this->components->twoColumnDetail('Skipped (will retry next run)', (string) $skipped);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Call>
     */
    private function fetchStaleCalls(): Collection
    {
        return Call::query()
            ->with(['caller.server'])
            ->where(function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->where('status', CallStatus::Initiated)
                        ->whereNotNull('initiated_at')
                        ->where('initiated_at', '<=', now()->subMinutes(self::RECONCILE_INITIATED_AFTER_MINUTES));
                })->orWhere(function (Builder $q) {
                    $q->where('status', CallStatus::Ringing)
                        ->whereNotNull('ringing_at')
                        ->where('ringing_at', '<=', now()->subMinutes(self::RECONCILE_RINGING_AFTER_MINUTES));
                })->orWhere(function (Builder $q) {
                    $q->where('status', CallStatus::Answered)
                        ->whereNotNull('answered_at')
                        ->where('answered_at', '<=', now()->subMinutes(self::RECONCILE_ANSWERED_AFTER_MINUTES));
                });
            })
            ->get();
    }

    private function activateAsteriskConnection(Server $server): void
    {
        Cdr::using(
            $server->database_host,
            (string) $server->database_username,
            (string) $server->database_password,
        );
    }

    private function reconcileCall(Call $call): string
    {
        return $call->unique_id === null
            ? $this->reconcileWithoutUniqueId($call)
            : $this->reconcileWithUniqueId($call);
    }

    private function reconcileWithUniqueId(Call $call): string
    {
        $cels = Cel::where('uniqueid', $call->unique_id)
            ->whereIn('eventtype', ['CHAN_START', 'ANSWER', 'HANGUP'])
            ->orderBy('eventtime')
            ->get();

        $hangup = $cels->firstWhere('eventtype', 'HANGUP');

        if ($hangup && $this->finalizeFromHangupCel($call, $hangup)) {
            return 'reconciled';
        }

        $answer = $cels->firstWhere('eventtype', 'ANSWER');

        if ($answer && in_array($call->status, [CallStatus::Initiated, CallStatus::Ringing], true)) {
            $chanStart = $cels->firstWhere('eventtype', 'CHAN_START');

            $call->update([
                'status' => CallStatus::Answered->value,
                'answered_at' => $answer->eventtime ?? now(),
                'ringing_at' => $call->ringing_at ?? $chanStart?->eventtime,
            ]);

            return 'reconciled';
        }

        $chanStart = $cels->firstWhere('eventtype', 'CHAN_START');

        if ($chanStart && $call->status === CallStatus::Initiated) {
            $call->update([
                'status' => CallStatus::Ringing->value,
                'ringing_at' => $chanStart->eventtime ?? now(),
            ]);

            return 'reconciled';
        }

        // No CEL events visible — try CDR (in case CEL is disabled on the box)
        $cdr = Cdr::where('uniqueid', $call->unique_id)->first();

        if ($cdr && $this->finalizeFromCdr($call, $cdr)) {
            return 'reconciled';
        }

        if ($this->isPastHardFail($call)) {
            $this->markFailed($call, 'no asterisk records found for unique_id');

            return 'failed';
        }

        return 'skipped';
    }

    private function reconcileWithoutUniqueId(Call $call): string
    {
        $callerNumber = $call->caller?->caller_number;

        if (! $callerNumber) {
            if ($this->isPastHardFail($call)) {
                $this->markFailed($call, 'no caller number for matching');

                return 'failed';
            }

            return 'skipped';
        }

        $candidates = $this->findCdrCandidates($call, $callerNumber);

        if ($candidates->count() === 1) {
            $cdr = $candidates->first();

            $call->update(['unique_id' => $cdr->uniqueid]);

            return $this->finalizeFromCdr($call, $cdr) ? 'reconciled' : 'skipped';
        }

        if ($candidates->count() > 1) {
            Log::warning('CallsReconcile: ambiguous CDR candidates for call without unique_id', [
                'call_id' => $call->id,
                'count' => $candidates->count(),
            ]);
        }

        if ($this->isPastHardFail($call)) {
            $reason = $candidates->isEmpty()
                ? 'no cdr candidates matched caller+destination'
                : 'ambiguous cdr candidates';

            $this->markFailed($call, $reason);

            return 'failed';
        }

        return 'skipped';
    }

    private function findCdrCandidates(Call $call, string $callerNumber): Collection
    {
        $digits = preg_replace('/\D+/', '', (string) $call->phone_number) ?? '';
        $last9 = mb_strlen($digits) >= 9 ? mb_substr($digits, -9) : $digits;

        $start = ($call->initiated_at ?? $call->called_at ?? $call->updated_at)
            ?->copy()
            ->subMinute();

        return Cdr::where('src', $callerNumber)
            ->whereBetween('calldate', [$start ?? now()->subHour(), now()])
            ->where(function (Builder $q) use ($digits, $last9) {
                $q->where('dst', $digits)
                    ->orWhere('dst', 'LIKE', '%'.$last9);
            })
            ->orderBy('calldate')
            ->get();
    }

    private function finalizeFromHangupCel(Call $call, Cel $hangup): bool
    {
        $hangupCause = (int) ($hangup->extra['hangupcause'] ?? 0);

        $status = match ($hangupCause) {
            16 => CallStatus::Completed,
            17 => CallStatus::Busy,
            19 => CallStatus::NotAnswered,
            21, 0 => CallStatus::Failed,
            default => null,
        };

        if ($status === null) {
            return false;
        }

        $cdr = Cdr::where('uniqueid', $call->unique_id)->first();
        $duration = (int) ($cdr?->billsec ?? 0);

        if ($status === CallStatus::Completed && $duration === 0) {
            $status = CallStatus::Busy;
        }

        $call->update([
            'status' => $status->value,
            'hangup_cause' => (string) $hangupCause,
            'ended_at' => $hangup->eventtime ?? now(),
            'duration' => $duration,
        ]);

        return true;
    }

    private function finalizeFromCdr(Call $call, Cdr $cdr): bool
    {
        $disposition = mb_strtoupper((string) ($cdr->disposition ?? ''));
        $billsec = (int) ($cdr->billsec ?? 0);

        $status = match ($disposition) {
            'ANSWERED' => $billsec > 0 ? CallStatus::Completed : CallStatus::Busy,
            'BUSY' => CallStatus::Busy,
            'NO ANSWER' => CallStatus::NotAnswered,
            'FAILED', 'CONGESTION' => CallStatus::Failed,
            default => null,
        };

        if ($status === null) {
            return false;
        }

        $endedAt = $cdr->calldate
            ? CarbonImmutable::parse($cdr->calldate)->addSeconds((int) ($cdr->duration ?? $billsec))
            : now();

        $call->update([
            'status' => $status->value,
            'duration' => $billsec,
            'ended_at' => $endedAt,
        ]);

        return true;
    }

    private function isPastHardFail(Call $call): bool
    {
        return match ($call->status) {
            CallStatus::Initiated => $call->initiated_at?->lte(now()->subMinutes(self::FAIL_INITIATED_AFTER_MINUTES)) ?? false,
            CallStatus::Ringing => $call->ringing_at?->lte(now()->subMinutes(self::FAIL_RINGING_AFTER_MINUTES)) ?? false,
            CallStatus::Answered => $call->answered_at?->lte(now()->subMinutes(self::FAIL_ANSWERED_AFTER_MINUTES)) ?? false,
            default => false,
        };
    }

    private function markFailed(Call $call, string $reason): void
    {
        $previous = $call->status?->value;

        $call->update(['status' => CallStatus::Failed->value]);

        Log::info('CallsReconcile: marked call failed', [
            'call_id' => $call->id,
            'previous_status' => $previous,
            'reason' => $reason,
        ]);
    }
}

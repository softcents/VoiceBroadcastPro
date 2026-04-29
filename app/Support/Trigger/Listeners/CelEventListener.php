<?php

declare(strict_types=1);

namespace App\Support\Trigger\Listeners;

use App\Enums\CallStatus;
use App\Models\Asterisk\Cdr;
use App\Models\Call;
use MySQLReplication\Event\DTO\WriteRowsDTO;

final class CelEventListener
{
    public static function handle(WriteRowsDTO $event): void
    {
        $row = $event->values[0] ?? null;

        if (! is_array($row) || ! isset($row['eventtype'], $row['uniqueid'])) {
            return;
        }

        match ($row['eventtype']) {
            'CHAN_START' => self::handleChanStart($row),
            'ANSWER' => self::handleAnswer($row),
            'HANGUP' => self::handleHangup($row),
            default => null,
        };
    }

    private static function handleChanStart(array $row): void
    {
        Call::where('unique_id', $row['uniqueid'])->update([
            'status' => CallStatus::Ringing->value,
            'ringing_at' => now(),
        ]);
    }

    private static function handleAnswer(array $row): void
    {
        Call::where('unique_id', $row['uniqueid'])->update([
            'status' => CallStatus::Answered->value,
            'answered_at' => now(),
        ]);
    }

    private static function handleHangup(array $row): void
    {
        if (empty($row['extra'])) {
            return;
        }

        $extra = json_decode($row['extra'], true);

        if (! is_array($extra) || ! isset($extra['hangupcause'])) {
            return;
        }

        $hangupCause = (int) $extra['hangupcause'];

        // Asterisk Q.850 hangup causes
        $status = match ($hangupCause) {
            16 => CallStatus::Completed,   // Normal clearing — call answered and ended
            17 => CallStatus::Busy,        // User busy
            19 => CallStatus::NotAnswered, // No answer
            21, 0 => CallStatus::Failed,   // Call rejected / Unknown / cancelled before connect
            default => null,
        };

        if ($status === null) {
            return;
        }

        $cdr = Cdr::using('103.191.241.11', 'cdrread', 'StrongPass123')
            ->where('uniqueid', $row['uniqueid'])
            ->first();

        $duration = (int) ($cdr?->billsec ?? 0);

        // If marked completed but never actually answered, treat as busy
        if ($status === CallStatus::Completed && $duration === 0) {
            $status = CallStatus::Busy;
        }

        $call = Call::where('unique_id', $row['uniqueid'])->first();

        if (! $call) {
            return;
        }

        $call->update([
            'status' => $status,
            'hangup_cause' => (string) $hangupCause,
            'ended_at' => now(),
            'duration' => $duration,
        ]);
    }
}

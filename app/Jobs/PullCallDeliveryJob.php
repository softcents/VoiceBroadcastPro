<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Asterisk\Cdr;
use App\Models\Call;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PullCallDeliveryJob implements ShouldQueue
{
    use Queueable;

    public const int MAX_ATTEMPTS = 7;

    public function __construct(public readonly int $callId) {}

    public function handle(): void
    {
        $call = Call::query()
            ->whereId($this->callId)
            ->whereNotNull('unique_id')
            ->withWhereHas('caller.server')
            ->lockForUpdate()
            ->first();

        if (! $call) {
            return; // Call not found or unique_id is null, nothing to do
        }

        $server = $call->caller->server;

        if (! $server) {
            return; // Server not found, cannot pull delivery status
        }

        $cdr = Cdr::using($server->database_host, $server->database_username, $server->database_password)
            ->where('uniqueid', $call->unique_id)
            ->first();

        if (! $cdr) {
            $attempt = $call->poll_attempt ?? 1;

            if ($attempt >= self::MAX_ATTEMPTS) {
                // Exceeded max attempts, mark call as failed. Refund the money to the user.
                $call->update([
                    'status' => 'Failed',
                    'poll_attempt' => $attempt,
                    'next_poll_at' => null,
                    'cost' => 0, // Reset cost to 0 since we are refunding
                ]);

                $lockedUser = User::lockForUpdate()->find($call->user_id);
                if ($lockedUser) {
                    $lockedUser->increment('balance', $call->cost); // Refund the cost to the user

                    $call->transactions()->create([
                        'type' => TransactionType::Credit,
                        'amount' => $call->cost,
                        'balance_before' => $lockedUser->balance - $call->cost,
                        'balance_after' => $lockedUser->balance,
                        'currency' => 'BDT',
                        'description' => "Refund for call ID {$call->id} after failed delivery",
                    ]);
                }

                return;
            }

            $nextAttempt = $call->poll_attempt + 1;

            $call->update([
                'poll_attempt' => $nextAttempt,
                'next_poll_at' => now()->addSeconds($this->delayForAttempt($nextAttempt)),
            ]);

            return; // CDR not found, will retry later
        }

        // Recalculate cost based on actual call duration
        $call->update([
            'status' => CallStatus::Completed,
            'duration' => $cdr->billsec,
            'poll_attempt' => null,
            'next_poll_at' => null,
        ]);
    }

    private function delayForAttempt(int $attempt): int
    {
        return match (true) {
            $attempt <= 3 => 30, // 30 seconds for attempts 1-3
            $attempt <= 5 => 60, // 1 minute for attempts 4-5
            default => 120, // 2 minutes for attempts 6-7
        };
    }
}

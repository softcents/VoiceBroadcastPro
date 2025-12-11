<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessOtpCall implements ShouldQueue
{
    use Batchable, Queueable;

    protected ?Call $call;

    public function __construct(int $callId)
    {
        $this->call = Call::with(['caller.server'])->find($callId);
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $user = $this->call->user;

        $pulseRate = $user->pulse_rate;

        if (! $user || $user->balance < $pulseRate) {
            $this->call->update(['status' => CallStatus::Failed]);

            return;
        }

        $cost = $pulseRate;

        // Deduct balance and create transaction
        try {
            DB::beginTransaction();

            $user->decrement('balance', $cost);

            $user->transactions()->create([
                'type' => TransactionType::Debit,
                'amount' => $cost,
                'currency' => 'BDT',
                'description' => "Initial charge for OTP call ID {$this->call->id}",
                'reference_type' => Call::class,
                'reference_id' => $this->call->id,
            ]);

            $this->call->cost = $cost;
            $this->call->saveQuietly();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to deduct balance for OTP Call ID: {$this->call->id}. Error: {$e->getMessage()}");
            $this->call->update(['status' => CallStatus::Failed]);

            return;
        }

        $recipientNumber = $this->call->phone_number;
        $trunkName = $this->call->caller->trunk_name;
        $callerNumber = $this->call->caller->caller_number;
        $callerName = $this->call->caller->caller_name;
        $otp = $this->call->otp;

        $response = Http::withBasicAuth(
            username: $this->call->caller->server->username,
            password: $this->call->caller->server->password
        )
            ->baseUrl($this->call->caller->server->domain)
            ->post('ari/channels', [
                'endpoint' => "PJSIP/{$recipientNumber}@{$trunkName}",
                'priority' => 1,
                'callerId' => "{$callerName} <{$callerNumber}>",
                'app' => 'originate',
                'appArgs' => "otp,$otp",
            ]);

        if ($response->failed()) {
            // Refund balance
            DB::transaction(function () use ($user, $cost) {
                $user->increment('balance', $cost);

                $user->transactions()->create([
                    'type' => TransactionType::Credit,
                    'amount' => $cost,
                    'currency' => 'BDT',
                    'description' => "Refund for failed OTP call initiation ID {$this->call->id}",
                    'reference_type' => Call::class,
                    'reference_id' => $this->call->id,
                ]);

                $this->call->cost = 0;
                $this->call->saveQuietly();
            });

            throw new ConnectionException("Failed to initiate call for Call ID: {$this->call->id}");
        }

        $this->call->update([
            'unique_id' => $response->json('id'),
            'status' => CallStatus::Initiated,
            'called_at' => CarbonImmutable::createFromTimeString($response->json('creationtime')),
        ]);
    }
}

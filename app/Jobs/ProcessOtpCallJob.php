<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessOtpCallJob implements ShouldQueue
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
                'transactionable_type' => Call::class,
                'transactionable_id' => $this->call->id,
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

        $username = $this->call->caller->server->ari_username;
        $password = $this->call->caller->server->ari_password;

        $recipientNumber = $this->call->phone_number;
        $trunkName = $this->call->caller->trunk_name;
        $callerName = $this->call->caller->caller_name;
        $callerNumber = $this->call->caller->caller_number;
        $otp = $this->call->otp;

        $response = Http::withBasicAuth($username, $password)
            ->baseUrl($this->call->caller->server->ari_base_url)
            ->post('ari/channels', [
                'endpoint' => "PJSIP/{$recipientNumber}@{$trunkName}",
                'extension' => 'frolax.agency',
                'context' => 'outgoing-http',
                'priority' => 1,
                'callerId' => "{$callerName} <{$callerNumber}>",
                'variables' => [
                    'STEP_COUNT' => '3',
                    'STEP_1_TYPE' => 'url',
                    'STEP_1_VALUE' => url('sounds/pre-otp.wav'),
                    'STEP_2_TYPE' => 'digits',
                    'STEP_2_VALUE' => $otp,
                    'STEP_3_TYPE' => 'url',
                    'STEP_3_VALUE' => url('sounds/post-otp.wav'),
                ],
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
                    'transactionable_type' => Call::class,
                    'transactionable_id' => $this->call->id,
                ]);

                $this->call->cost = 0;
                $this->call->saveQuietly();
            });

            throw new ConnectionException("Failed to initiate call for Call ID: {$this->call->id}");
        }

        $this->call->update([
            'unique_id' => $response->json('id'),
            'status' => CallStatus::Processing,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        if (isset($this->call)) {
            $this->call->update([
                'status' => CallStatus::Failed,
            ]);
        }

        Log::error('ProcessOtpCall failed for Call ID: '.($this->call?->id ?? 'unknown'), [
            'exception' => $exception->getMessage(),
        ]);
    }
}

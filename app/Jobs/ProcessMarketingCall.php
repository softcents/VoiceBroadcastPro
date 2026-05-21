<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessMarketingCall implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    private ?Call $call = null;

    public function __construct(
        public readonly int $callId
    ) {}

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->call = Call::with(['audio', 'caller.server', 'user'])->find($this->callId);

        if (! $this->call) {
            Log::warning("Call ID {$this->callId} not found in queue");

            return;
        }

        $user = $this->call->user;

        if (! $user) {
            $this->failCall('User not found');

            return;
        }

        // Validate required relationships
        if (! $this->call->audio) {
            $this->refundAndFail($user, 'Audio record not found');
        }

        if (! $this->call->caller) {
            $this->refundAndFail($user, 'Caller configuration not found');
        }

        if (! $this->call->caller->server) {
            $this->refundAndFail($user, 'Server configuration not found');
        }

        // Check audio file exists before proceeding
        $audioPath = $this->call->audio->converted_path;

        if (! $audioPath || ! Storage::exists($audioPath)) {
            $this->refundAndFail($user, 'Audio file not found');
        }

        // Initiate the call via API
        $this->initiateCall($audioPath);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        if ($this->call) {
            // Only update status if not already failed (avoid double-update)
            if ($this->call->status !== CallStatus::Failed) {
                $this->call->update(['status' => CallStatus::Failed]);
            }

            Log::error('ProcessMarketingCall job failed', [
                'call_id' => $this->call->id,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        } else {
            Log::error('ProcessMarketingCall job failed - Call not loaded', [
                'call_id' => $this->callId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Initiate the call via the telephony API.
     *
     * @throws Throwable
     */
    private function initiateCall(string $audioPath): void
    {
        try {
            $username = $this->call->caller->server->ari_username;
            $password = $this->call->caller->server->ari_password;

            $recipientNumber = $this->call->phone_number;
            $trunkName = $this->call->caller->trunk_name;
            $callerName = $this->call->caller->caller_name;
            $callerNumber = $this->call->caller->caller_number;

            $response = Http::timeout(30)
                ->withBasicAuth($username, $password)
                ->baseUrl($this->call->caller->server->ari_base_url)
                ->post('ari/channels', [
                    'endpoint' => "PJSIP/{$recipientNumber}@{$trunkName}",
                    'extension' => 'frolax.agency',
                    'context' => 'outgoing-http',
                    'priority' => 1,
                    'callerId' => "{$callerName} <{$callerNumber}>",
                    'variables' => [
                        'STEP_COUNT' => '1',
                        'STEP_1_TYPE' => 'url',
                        'STEP_1_VALUE' => getFileUrl($audioPath),
                    ],
                ]);

            if ($response->failed()) {
                $serverError = $response->json('message')
                    ?? $response->json('error')
                    ?? 'Unknown API error';
                $statusCode = $response->status();

                Log::error("API request failed for Call ID {$this->call->id}", [
                    'status' => $statusCode,
                    'error' => $serverError,
                    'response' => $response->body(),
                ]);

                $this->refundAndFail(
                    $this->call->user,
                    "API request failed with status {$statusCode}."
                );
            }

            $uniqueId = $response->json('id');

            if (! $uniqueId) {
                $this->refundAndFail($this->call->user, 'No unique ID returned from API');
            }

            $this->call->update([
                'unique_id' => $uniqueId,
                'status' => CallStatus::Processing,
            ]);

        } catch (Exception $e) {
            Log::error("Exception during API call for Call ID {$this->call->id}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->refundAndFail($this->call->user, 'Server API exception');
        }
    }

    /**
     * Refund the reserved balance and fail the call.
     *
     * @throws Throwable
     */
    private function refundAndFail(User $user, string $reason): never
    {
        $cost = $this->call->cost;

        DB::transaction(function () use ($user, $cost, $reason) {
            // Refund the user's balance
            $user->increment('balance', $cost);

            // Create transaction record
            $user->transactions()->create([
                'type' => TransactionType::Credit,
                'amount' => $cost,
                'currency' => 'BDT',
                'description' => "Refund for call ID {$this->call->id}: {$reason}",
                'transactionable_type' => Call::class,
                'transactionable_id' => $this->call->id,
            ]);

            // Update call status and reset cost
            $this->call->update([
                'cost' => 0,
                'status' => CallStatus::Failed,
            ]);
        });

        throw new Exception("Call ID {$this->call->id} failed: {$reason}");
    }

    /**
     * Fail the call without refunding (used when no cost was incurred).
     */
    private function failCall(string $reason): void
    {
        $this->call->update(['status' => CallStatus::Failed]);

        Log::warning('Call failed without refund', [
            'call_id' => $this->call->id,
            'reason' => $reason,
        ]);
    }
}

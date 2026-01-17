<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;
use App\Models\User;
use Carbon\CarbonImmutable;
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

        if (! $audioPath || ! Storage::disk('public')->exists($audioPath)) {
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
            $response = Http::timeout(30)
                ->withBasicAuth(
                    username: $this->call->caller->server->username,
                    password: $this->call->caller->server->password
                )
                ->baseUrl($this->call->caller->server->domain)
                ->post('ari/channels', [
                    'endpoint' => "PJSIP/{$this->call->phone_number}@{$this->call->caller->trunk_name}",
                    'priority' => 1,
                    'callerId' => "{$this->call->caller->caller_name} <{$this->call->caller->caller_number}>",
                    'app' => 'originate',
                    'appArgs' => 'marketing,'.Storage::disk('public')->url($audioPath),
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json('message')
                    ?? $response->json('error')
                    ?? 'Unknown API error';
                $statusCode = $response->status();

                Log::error("API request failed for Call ID {$this->call->id}", [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $response->body(),
                ]);

                $this->refundAndFail(
                    $this->call->user,
                    "API request failed with status {$statusCode}: {$errorMessage}"
                );
            }

            $uniqueId = $response->json('id');
            $creationTime = $response->json('creationtime');

            if (! $uniqueId) {
                $this->refundAndFail($this->call->user, 'No unique ID returned from API');
            }

            $this->call->update([
                'unique_id' => $uniqueId,
                'status' => CallStatus::Initiated,
                'called_at' => $creationTime
                    ? CarbonImmutable::createFromTimeString($creationTime)
                    : CarbonImmutable::now(),
            ]);

            Log::info('Call initiated successfully', [
                'call_id' => $this->call->id,
                'unique_id' => $uniqueId,
            ]);

        } catch (Exception $e) {
            Log::error("Exception during API call for Call ID {$this->call->id}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->refundAndFail($this->call->user, "API exception: {$e->getMessage()}");
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
                'reference_type' => Call::class,
                'reference_id' => $this->call->id,
            ]);

            // Update call status and reset cost
            $this->call->update([
                'cost' => 0,
                'status' => CallStatus::Failed,
            ]);
        });

        Log::warning('Call refunded and failed', [
            'call_id' => $this->call->id,
            'reason' => $reason,
            'refunded_amount' => $cost,
        ]);

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

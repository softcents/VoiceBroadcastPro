<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Jobs\Concerns\RefundsCallCost;
use App\Models\Call;
use Exception;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class InitiateCallJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable, RefundsCallCost;

    public int $uniqueFor = 60;

    private ?Call $call = null;

    public function __construct(
        public readonly int $callId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->callId;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->call = Call::with(['audio', 'caller.server', 'user'])
            ->withoutGlobalScopes()
            ->find($this->callId);

        if (! $this->call) {
            Log::warning("Call ID {$this->callId} not found in queue");

            return;
        }

        // Already settled — nothing to do.
        if (in_array($this->call->status, [CallStatus::Failed, CallStatus::Completed, CallStatus::Processing], true)) {
            return;
        }

        if (! $this->call->user) {
            $this->refundCallCost($this->callId, 'User not found');

            return;
        }

        if (! $this->call->audio) {
            $this->refundCallCost($this->callId, 'Audio record not found');

            return;
        }

        if (! $this->call->caller?->server) {
            $this->refundCallCost($this->callId, 'Caller/server configuration not found');

            return;
        }

        $audioPath = $this->call->audio->converted_path;

        if (! $audioPath || ! Storage::exists($audioPath)) {
            $this->refundCallCost($this->callId, 'Audio file not found');

            return;
        }

        $this->initiateCall();
    }

    public function failed(Throwable $exception): void
    {
        try {
            $this->refundCallCost($this->callId, 'Job permanently failed: '.$exception->getMessage());
        } catch (Throwable $e) {
            Log::error('Refund failed during failed() handler', [
                'call_id' => $this->callId,
                'exception' => $e->getMessage(),
            ]);
        }

        Log::error('ProcessMarketingCall job failed', [
            'call_id' => $this->callId,
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * @throws Throwable
     */
    private function initiateCall(): void
    {
        try {
            $server = $this->call->caller->server;

            if (config('app.local_call', false)) {
                $phone = '1111';
                $trunk = '2222';
                $callerName = '2222';
                $callerNumber = '2222';
            } else {
                $phone = $this->call->phone_number;
                $trunk = $this->call->caller->trunk_name;
                $callerName = $this->call->caller->caller_name;
                $callerNumber = $this->call->caller->caller_number;
            }

            $response = Http::baseUrl($server->ari_base_url)
                ->withBasicAuth($server->ari_username, $server->ari_password)
                ->timeout(30)
                ->connectTimeout(10)
                ->asJson()
                ->acceptJson()
                ->post('ari/channels', [
                    'endpoint' => "PJSIP/{$phone}@$trunk",
                    'app' => 'MyStasisApp',
                    'callerId' => "$callerName <$callerNumber>",
                    'appArgs' => "$this->callId",
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

                $this->refundCallCost($this->callId, "API request failed with status {$statusCode}");

                return;
            }

            $uniqueId = $response->json('id');

            if (! $uniqueId) {
                $this->refundCallCost($this->callId, 'No unique ID returned from API');

                return;
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

            $this->refundCallCost($this->callId, 'Server API exception: '.$e->getMessage());
        }
    }
}

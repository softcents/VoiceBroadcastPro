<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Jobs\Concerns\RefundsCallCost;
use App\Jobs\Middleware\LimitCallerCalls;
use App\Jobs\Middleware\LimitServerCalls;
use App\Jobs\UpdateCampaignStatus;
use App\Models\Call;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessOtpCallJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable, RefundsCallCost;

    public int $tries = 50;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public int $uniqueFor = 3600;

    private ?Call $call = null;

    public function __construct(public readonly int $callId) {}

    public function uniqueId(): string
    {
        return (string) $this->callId;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new LimitServerCalls($this->callId),
            new LimitCallerCalls($this->callId),
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->call = Call::with(['caller.server'])
            ->withoutGlobalScopes()
            ->find($this->callId);

        if (! $this->call) {
            Log::warning("OTP Call ID {$this->callId} not found in queue");

            return;
        }

        // Already settled — nothing to do.
        if (in_array($this->call->status, [CallStatus::Failed, CallStatus::Completed, CallStatus::Processing], true)) {
            return;
        }

        if (! $this->call->caller?->server) {
            $this->refundCallCost($this->callId, 'Caller/server configuration missing');

            return;
        }

        $this->initiateCall();
    }

    public function failed(Throwable $exception): void
    {
        try {
            $this->refundCallCost($this->callId, 'Job permanently failed: '.$exception->getMessage());
        } catch (Throwable $e) {
            Log::error('OTP refund failed during failed() handler', [
                'call_id' => $this->callId,
                'exception' => $e->getMessage(),
            ]);
        }

        Log::error("ProcessOtpCall failed for Call ID: {$this->callId}", [
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

            $response = Http::timeout(30)
                ->withBasicAuth($server->ari_username, $server->ari_password)
                ->baseUrl($server->ari_base_url)
                ->post('ari/channels', [
                    'endpoint' => "PJSIP/{$this->call->phone_number}@{$this->call->caller->trunk_name}",
                    'extension' => 'frolax.agency',
                    'context' => 'outgoing-http',
                    'priority' => 1,
                    'callerId' => "{$this->call->caller->caller_name} <{$this->call->caller->caller_number}>",
                    'variables' => [
                        'STEP_COUNT' => '3',
                        'STEP_1_TYPE' => 'url',
                        'STEP_1_VALUE' => url('sounds/pre-otp.wav'),
                        'STEP_2_TYPE' => 'digits',
                        'STEP_2_VALUE' => $this->call->otp,
                        'STEP_3_TYPE' => 'url',
                        'STEP_3_VALUE' => url('sounds/post-otp.wav'),
                    ],
                ]);

            if ($response->failed()) {
                Log::error("OTP API request failed for Call ID {$this->call->id}", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                $this->refundCallCost($this->callId, "API request failed with status {$response->status()}");

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

            if ($this->call->campaign_id) {
                UpdateCampaignStatus::dispatch($this->call->campaign_id);
            }
        } catch (Exception $e) {
            Log::error("Exception during OTP API call for Call ID {$this->call->id}", [
                'exception' => $e->getMessage(),
            ]);

            $this->refundCallCost($this->callId, 'Server API exception: '.$e->getMessage());
        }
    }
}

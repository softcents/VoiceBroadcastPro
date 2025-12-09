<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Models\Call;
use App\Settings\CallingSetting;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $user = $this->call->user;
        $settings = app(CallingSetting::class);

        if (! $user || $user->balance < $settings->rate_per_minute) {
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
            throw new ConnectionException("Failed to initiate call for Call ID: {$this->call->id}");
        }

        $this->call->update([
            'unique_id' => $response->json('id'),
            'status' => CallStatus::Initiated,
            'called_at' => CarbonImmutable::createFromTimeString($response->json('creationtime')),
        ]);
    }
}

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

final class ProcessCall implements ShouldQueue
{
    use Batchable, Queueable;

    protected ?Call $call;

    public function __construct(int $callId)
    {
        $this->call = Call::with(['audio', 'caller.server'])->find($callId);
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

        $response = Http::withBasicAuth(
            username: $this->call->caller->server->username,
            password: $this->call->caller->server->password
        )
            ->baseUrl($this->call->caller->server->domain)
            ->post('ari/channels', [
                'endpoint' => "PJSIP/{$this->call->phone_number}@{$this->call->caller->caller_number}",
                'priority' => 1,
                'callerId' => "{$this->call->caller->caller_name} <{$this->call->caller->caller_number}>",
                'app' => 'originate',
                'appArgs' => url($this->call->audio->converted_path),
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

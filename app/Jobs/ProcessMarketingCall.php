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
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessMarketingCall implements ShouldQueue
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
     * @throws Throwable
     */
    public function handle(): void
    {
        $user = $this->call->user;

        $pulseRate = $user->pulse_rate;
        $pulseDuration = $user->pulse_duration;
        $audioDuration = $this->call->audio->duration;

        $totalPulses = ceil($audioDuration / $pulseDuration);
        $cost = $totalPulses * $pulseRate;

        if (! $user || $user->balance < $cost) {
            $this->call->update(['status' => CallStatus::Failed]);

            return;
        }

        // Deduct balance and create transaction
        try {
            DB::beginTransaction();

            $user->decrement('balance', $cost);

            $user->transactions()->create([
                'type' => TransactionType::Debit,
                'amount' => $cost,
                'currency' => 'BDT',
                'description' => "Initial charge for call ID {$this->call->id} ($totalPulses pulses)",
                'reference_type' => Call::class,
                'reference_id' => $this->call->id,
            ]);

            $this->call->cost = $cost;
            $this->call->saveQuietly();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to deduct balance for Call ID: {$this->call->id}. Error: {$e->getMessage()}");
            $this->call->update(['status' => CallStatus::Failed]);

            return;
        }

        $recipientNumber = $this->call->phone_number;
        $trunkName = $this->call->caller->trunk_name;
        $callerNumber = $this->call->caller->caller_number;
        $callerName = $this->call->caller->caller_name;
        $audioUrl = Storage::disk('public')->url($this->call->audio->converted_path);

        $response = Http::withBasicAuth(
            username: $this->call->caller->server->username,
            password: $this->call->caller->server->password
        )
            ->baseUrl($this->call->caller->server->domain)
            ->post('ari/channels', [
                'endpoint' => "PJSIP/$recipientNumber@$trunkName",
                'priority' => 1,
                'callerId' => "$callerName <$callerNumber>",
                'app' => 'originate',
                'appArgs' => "marketing,$audioUrl",
            ]);

        if ($response->failed()) {
            // Refund balance
            DB::transaction(function () use ($user, $cost) {
                $user->increment('balance', $cost);

                $user->transactions()->create([
                    'type' => TransactionType::Credit,
                    'amount' => $cost,
                    'currency' => 'BDT',
                    'description' => "Refund for failed call initiation ID {$this->call->id}",
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

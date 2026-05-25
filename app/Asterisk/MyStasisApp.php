<?php

declare(strict_types=1);

namespace App\Asterisk;

use App\Contracts\AsteriskStasisApp;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Enums\TransactionType;
use App\Models\Asterisk\Cdr;
use App\Models\Call;
use App\Models\User;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use JsonException;
use OpiyOrg\AriClient\Client\Rest\Resource\Channels;
use OpiyOrg\AriClient\Client\Rest\Resource\Events;
use OpiyOrg\AriClient\Exception\AsteriskRestInterfaceException;
use OpiyOrg\AriClient\Model\Message\Event\ChannelHangupRequest;
use OpiyOrg\AriClient\Model\Message\Event\PlaybackFinished;
use OpiyOrg\AriClient\Model\Message\Event\StasisStart;
use OpiyOrg\AriClient\StasisApplicationInterface;

final class MyStasisApp implements AsteriskStasisApp, StasisApplicationInterface
{
    public function __construct(
        public Channels $channels,
        public Events $events,
        public Factory $components
    ) {}

    /**
     * @throws AsteriskRestInterfaceException
     * @throws JsonException
     */
    public function onAriEventStasisStart(StasisStart $stasisStart): void
    {
        ray($stasisStart)->label('Received StasisStart event')->showApp()->green();
        $channelId = $stasisStart->getChannel()->getId();

        $callId = (int) $stasisStart->args[0] ?? null;
        if (! $callId) {
            $this->channels->hangup($channelId);

            return;
        }

        $call = $this->findCall($callId);
        if (! $call) {
            $this->channels->hangup($channelId);

            return;
        }

        $audioPath = $call->audio->converted_path;

        if (! $audioPath || ! Storage::exists($audioPath)) {
            $this->channels->hangup($channelId);

            return;
        }

        switch ($call->type) {
            case CallType::Marketing:
                $this->channels->play($channelId, [
                    'sound:'.getFileUrl($audioPath),
                ]);
                break;
            case CallType::OTP:
                $this->channels->play($channelId, [
                    'sound:'.url('sounds/pre-otp.wav'),
                    'digits:'.$call->otp,
                    'sound:'.url('sounds/post-otp.wav'),
                    'digits:'.$call->otp,
                ]);
        }
    }

    /**
     * @throws AsteriskRestInterfaceException
     * @throws JsonException
     */
    public function onAriEventPlaybackFinished(PlaybackFinished $event): void
    {
        ray($event)->label('Received PlaybackFinished event')->showApp()->purple();

        $targetUri = $event->getPlayback()->targetUri;
        $channelId = str($targetUri)->after('channel:')->toString();

        $isChannelUp = collect($this->channels->list())
            ->where('id', $channelId)
            ->where('state', 'Up')
            ->isNotEmpty();

        if ($isChannelUp) {
            $this->channels->hangup($channelId);
        }
    }

    public function onAriEventChannelHangupRequest(ChannelHangupRequest $channelHangupRequest): void
    {
        $uniqueId = $channelHangupRequest->getChannel()->getId();

        $call = $this->findCall(null, $uniqueId);

        if (! $call) {
            // If we can't find the call, we can't find the CDR, so we just return.
            return;
        }

        $cdr = Cdr::using(
            host: $call->caller->server->database_host,
            username: $call->caller->server->database_username,
            password: $call->caller->server->database_password
        )
            ->where('uniqueid', $uniqueId)
            ->first();

        if (! $cdr) {
            $this->markCallAsFailed($call);

            return;
        }

        if ($cdr->billsec <= 0) {
            $this->markCallAsFailed($call);

            return;
        }

        $this->markCallAsCompleted($call, $cdr->billsec);
    }

    public function findCall(?int $callId = null, ?string $uniqueId = null): ?Call
    {
        if (! $callId && ! $uniqueId) {
            return null;
        }

        return Call::query()
            ->with(['caller.server', 'audio'])
            ->when($callId, fn (Builder $query) => $query->where('id', $callId))
            ->when($uniqueId, fn (Builder $query) => $query->orWhere('unique_id', $uniqueId))
            ->first();
    }

    private function markCallAsFailed(Call $call): void
    {
        if ($call->cost <= 0) {
            $call->update(['status' => CallStatus::Failed]);

            return;
        }

        $user = User::query()
            ->whereKey($call->user_id)
            ->lockForUpdate()
            ->first();

        if (! $user) {
            return;
        }

        $before = $user->balance;

        $user->increment('balance', $call->cost);

        $call->transactions()->create([
            'user_id' => $user->id,
            'type' => TransactionType::Credit,
            'amount' => $call->cost,
            'balance_before' => $before,
            'balance_after' => $before + $call->cost,
            'currency' => 'BDT',
            'description' => "Refund for call #$call->id",
        ]);

        $call->update(['status' => CallStatus::Failed, 'cost' => 0]);
    }

    private function markCallAsCompleted(Call $call, int $billSec): void
    {
        $user = User::query()
            ->whereKey($call->user_id)
            ->lockForUpdate()
            ->first();

        if (! $user) {
            return;
        }

        $actualCost = $this->calculateEstimatedCost($billSec, $user);
        $estimatedCost = (float) $call->cost;

        $diff = (float) $actualCost - $estimatedCost;

        // update call
        $call->update([
            'status' => CallStatus::Completed,
            'duration' => $billSec,
            'cost' => $actualCost,
        ]);

        // no change needed (tolerate float noise within 1 paisa)
        if (abs($diff) < 0.01) {
            return;
        }

        $before = $user->balance;

        if ($diff > 0) {
            // undercharged → collect extra
            $user->decrement('balance', $diff);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Debit,
                'amount' => $diff,
                'balance_before' => $before,
                'balance_after' => $before - $diff,
                'currency' => 'BDT',
                'description' => "Extra charge adjustment for call #{$call->id}",
            ]);
        } else {
            // overcharged → refund difference
            $refund = abs($diff);

            $user->increment('balance', $refund);

            $call->transactions()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Credit,
                'amount' => $refund,
                'balance_before' => $before,
                'balance_after' => $before + $refund,
                'currency' => 'BDT',
                'description' => "Refund adjustment for call #{$call->id}",
            ]);
        }
    }

    private function calculateEstimatedCost(int $duration, User $user): int|float
    {
        $pulseDuration = $user->pulse_duration ?? 60;
        $pulseRate = $user->pulse_rate ?? 0;

        if ($pulseDuration <= 0 || $pulseRate <= 0 || $duration <= 0) {
            return 0;
        }

        $pulses = (int) ceil($duration / $pulseDuration);

        return $pulses * $pulseRate;
    }
}

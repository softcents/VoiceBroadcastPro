<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Enums\CallStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AsteriskWebhookRequest;
use App\Models\Call;

final class AsteriskController extends Controller
{
    public function __invoke(AsteriskWebhookRequest $request)
    {
        $call = Call::whereUniqueId($request->validated('channel'))->firstOrFail();

        ray($request->all())->showApp();

        switch ($request->validated('event')) {
            case 'ringing':
                $call->update([
                    'status' => CallStatus::Ringing,
                    'ringing_at' => $request->validated('timestamp'),
                ]);
                break;
            case 'answered':
                $call->update([
                    'status' => CallStatus::Answered,
                    'answered_at' => $request->validated('timestamp'),
                ]);
                break;
            case 'completed':
                $call->update([
                    'status' => CallStatus::Completed,
                    'ended_at' => $request->validated('timestamp'),
                    'duration' => $call->answered_at?->diffInSeconds($request->validated('timestamp')),
                ]);
                break;
            case 'busy':
                $call->update([
                    'status' => CallStatus::Busy,
                    'ended_at' => $request->validated('timestamp'),
                ]);
                break;
            case 'not_answered':
                $call->update([
                    'status' => CallStatus::NotAnswered,
                    'ended_at' => $request->validated('timestamp'),
                ]);
                break;
            case 'failed':
                $call->update([
                    'status' => CallStatus::Failed,
                    'ended_at' => $request->validated('timestamp'),
                ]);
                break;
        }
    }
}

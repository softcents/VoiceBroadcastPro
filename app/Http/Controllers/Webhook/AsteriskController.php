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

        switch ($request->validated('event')) {
            case 'ringing':
            case 'answered':
                $call->update(['status' => CallStatus::Processing]);
                break;
            case 'completed':
                $call->update([
                    'status' => CallStatus::Completed,
                    'duration' => $call->duration,
                ]);
                break;
            case 'busy':
            case 'not_answered':
            case 'failed':
                $call->update(['status' => CallStatus::Failed]);
                break;
        }
    }
}

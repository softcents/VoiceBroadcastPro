<?php

namespace App\Enums;

enum CallStatus: string
{
    case Pending = 'pending';
    case Initiated = 'initiated';
    case Ringing = 'ringing';
    case Answered = 'answered';
    case Busy = 'busy';
    case NotAnswered = 'not_answered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}

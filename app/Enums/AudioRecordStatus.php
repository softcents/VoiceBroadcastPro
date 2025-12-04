<?php

namespace App\Enums;

enum AudioRecordStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

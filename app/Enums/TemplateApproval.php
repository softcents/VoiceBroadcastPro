<?php

namespace App\Enums;

enum TemplateApproval: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

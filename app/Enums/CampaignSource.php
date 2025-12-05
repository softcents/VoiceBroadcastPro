<?php

namespace App\Enums;

enum CampaignSource: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Phonebook = 'phonebook';
}

<?php

namespace App\Enums;

enum CampaignSource: string
{
    case Manual = 'manual';
    case Imported = 'imported';
    case Phonebook = 'phonebook';
}

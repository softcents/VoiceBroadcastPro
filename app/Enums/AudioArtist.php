<?php

namespace App\Enums;

enum AudioArtist: string
{
    // bn-BD Female
    case BnBdNabanitaNeural = 'bn-BD-NabanitaNeural';

    // bn-BD Male
    case BnBdPradeepNeural = 'bn-BD-PradeepNeural';

    public function language(): AudioLanguage
    {
        return match ($this) {
            self::BnBdNabanitaNeural, self::BnBdPradeepNeural => AudioLanguage::BnBD,
        };
    }

    public function gender(): AudioGender
    {
        return match ($this) {
            self::BnBdNabanitaNeural => AudioGender::Female,
            self::BnBdPradeepNeural => AudioGender::Male,
        };
    }
}

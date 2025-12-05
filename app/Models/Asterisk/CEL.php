<?php

namespace App\Models\Asterisk;

use Illuminate\Database\Eloquent\Model;

class CEL extends Model
{

    const REJECTED = 19;
    const ANSWERED = 16;
    const UNREACHABLE = 19;
    const BUSY = 0;


    protected $connection = 'asterisk';
    protected $table = 'cel';

    protected $casts = [
        'extra' => 'array'
    ];

    public static function findByUniqueId($uniqueId, $eventTypes = ['HANGUP'])
    {
        return self::whereIn('eventtype', $eventTypes)
            ->where('uniqueid', $uniqueId)
            ->first();
    }
}

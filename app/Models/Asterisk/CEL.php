<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use Illuminate\Database\Eloquent\Model;

final class CEL extends Model
{
    public const REJECTED = 19;

    public const ANSWERED = 16;

    public const UNREACHABLE = 19;

    public const BUSY = 0;

    protected $connection = 'asterisk';

    protected $table = 'cel';

    protected $casts = [
        'extra' => 'array',
    ];

    public static function findByUniqueId($uniqueId, $eventTypes = ['HANGUP'])
    {
        return self::whereIn('eventtype', $eventTypes)
            ->where('uniqueid', $uniqueId)
            ->first();
    }
}

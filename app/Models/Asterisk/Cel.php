<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use App\Asterisk\UsingAsteriskConnection;
use Illuminate\Database\Eloquent\Model;

final class Cel extends Model
{
    use UsingAsteriskConnection;

    public $timestamps = false;

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

<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use Illuminate\Database\Eloquent\Model;

final class CDR extends Model
{
    protected $connection = 'asterisk';

    protected $table = 'cdr';

    public static function findByUniqueId($uniqueId)
    {
        return self::where('uniqueid', '=', $uniqueId)->first();
    }
}

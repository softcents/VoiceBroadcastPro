<?php

namespace App\Models\Asterisk;

use Illuminate\Database\Eloquent\Model;

class CDR extends Model
{
    protected $connection = 'asterisk';
    protected $table = 'cdr';

    public static function findByUniqueId($uniqueId)
    {
        return self::where('uniqueid', '=', $uniqueId)->first();
    }
}

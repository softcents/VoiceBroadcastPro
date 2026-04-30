<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use App\Support\UsingAsteriskConnection;
use Illuminate\Database\Eloquent\Model;

final class Cdr extends Model
{
    use UsingAsteriskConnection;

    public $timestamps = false;

    protected $connection = 'asterisk';

    protected $table = 'cdr';

    protected $primaryKey = 'sequence';
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CallEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}

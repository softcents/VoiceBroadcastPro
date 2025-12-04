<?php

namespace App\Models;

use Database\Factories\SendingServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SendingServer extends Model
{
    /** @use HasFactory<SendingServerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'username',
        'password',
        'enabled',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sending_server_user');
    }
}

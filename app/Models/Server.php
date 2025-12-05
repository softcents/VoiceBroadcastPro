<?php

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'ari_domain',
        'ari_username',
        'ari_password',
        'database_host',
        'database_port',
        'database_name',
        'database_username',
        'database_password',
        'enabled',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'server_user');
    }

    public function callers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Caller::class);
    }
}

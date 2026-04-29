<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'ari_password' => 'encrypted',
        'database_password' => 'encrypted',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'server_user');
    }

    public function callers(): HasMany
    {
        return $this->hasMany(Caller::class);
    }

    protected function ariBaseUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->ari_scheme}://{$this->ari_host}".($this->ari_port ? ":{$this->ari_port}" : ''),
        );
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}

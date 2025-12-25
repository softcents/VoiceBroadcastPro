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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'server_user');
    }

    public function callers(): HasMany
    {
        return $this->hasMany(Caller::class);
    }

    protected function domain(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->scheme}://{$this->host}".($this->port ? ":{$this->port}" : ''),
        );
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}

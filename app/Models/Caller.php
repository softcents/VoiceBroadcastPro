<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CallerFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Caller extends Model
{
    /** @use HasFactory<CallerFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'caller_name',
        'caller_number',
        'enabled',
        'max_concurrency',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'max_concurrency' => 'integer',
    ];

    protected $appends = ['name'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caller_user');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->caller_name.' <'.$this->caller_number.'>',
        );
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}

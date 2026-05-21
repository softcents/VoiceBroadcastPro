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
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get the count of active calls for this caller.
     */
    public function activeCallsCount(): int
    {
        return Call::active()
            ->whereCallerId($this->id)
            ->count();
    }

    /**
     * Get the number of available slots for this caller.
     * Returns a high number (1000) if max_concurrency is 0 (unlimited).
     */
    public function availableSlots(): int
    {
        $limit = $this->max_concurrency;

        if ($limit <= 0) {
            return 1000; // Unlimited
        }

        return max(0, $limit - $this->activeCallsCount());
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

    #[Scope]
    protected function online(Builder $query): Builder
    {
        return $query->where('is_online', true);
    }

    #[Scope]
    protected function syncable(Builder $query): Builder
    {
        return $query->enabled()->where(function (Builder $query) {
            $query->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subMinutes(5));
        });
    }
}

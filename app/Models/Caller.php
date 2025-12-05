<?php

namespace App\Models;

use Database\Factories\CallerFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Caller extends Model
{
    /** @use HasFactory<CallerFactory> */
    use HasFactory;

    protected $fillable = [
        "sending_server_id",
        "caller_name",
        "caller_number",
        "enabled",
    ];

    public function sendingServer(): BelongsTo
    {
        return $this->belongsTo(SendingServer::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caller_user');
    }

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }
}

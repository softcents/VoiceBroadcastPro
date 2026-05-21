<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

#[Guarded(['id'])]
#[Hidden(['ari_password', 'database_password'])]
final class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $casts = [
        'ari_password' => 'encrypted',
        'database_password' => 'encrypted',
    ];

    public function callers(): HasMany
    {
        return $this->hasMany(Caller::class);
    }

    public function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->ariBaseUrl)
            ->withBasicAuth($this->ari_username, $this->ari_password)
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof ConnectionException;
            });
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

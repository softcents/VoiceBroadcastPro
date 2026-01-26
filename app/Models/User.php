<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserAudioType;
use App\Enums\UserType;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'type' => UserType::class,
        'audio_type' => UserAudioType::class,
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'float',
        'pulse_rate' => 'float',
        'pulse_duration' => 'integer',
        'rate' => 'float',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function audio(): HasMany
    {
        return $this->hasMany(Audio::class);
    }

    public function phonebooks(): HasMany
    {
        return $this->hasMany(Phonebook::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'server_user');
    }

    public function callers(): BelongsToMany
    {
        return $this->belongsToMany(Caller::class, 'caller_user');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Managed by AdminMiddleware and UserMiddleware
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function isAdmin(): bool
    {
        return $this->type === UserType::Admin;
    }

    public function isUser(): bool
    {
        return $this->type === UserType::User;
    }

    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    #[Scope]
    protected function admin(Builder $query): Builder
    {
        return $query->where('type', UserType::Admin);
    }

    #[Scope]
    protected function user(Builder $query): Builder
    {
        return $query->where('type', UserType::User);
    }
}

<?php

namespace App\Models;

use App\Enums\UserType;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

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

    public function sendingServers(): BelongsToMany
    {
        return $this->belongsToMany(SendingServer::class, 'sending_server_user');
    }

    public function senderIds(): BelongsToMany
    {
        return $this->belongsToMany(SenderId::class, 'sender_id_user');
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

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->type === UserType::Admin;
        }

        if ($panel->getId() === 'user') {
            return $this->type === UserType::User;
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Models\Scopes\OwnedByAuthUser;
use App\Observers\CallObserver;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[ScopedBy(OwnedByAuthUser::class)]
#[ObservedBy(CallObserver::class)]
final class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => CallStatus::class,
        'type' => CallType::class,
        'phone_number' => E164PhoneNumberCast::class,
        'called_at' => 'datetime',
        'ringing_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'duration' => 'float',
        'cost' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(Caller::class);
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }
}

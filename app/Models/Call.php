<?php

namespace App\Models;

use App\Enums\CallStatus;
use App\Models\Scopes\OwnedByAuthUser;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

#[ScopedBy(OwnedByAuthUser::class)]
class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'contact_id',
        'caller_id',
        'phone_number',
        'content',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CallStatus::class,
            'phone_number' => E164PhoneNumberCast::class,
        ];
    }

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
}

<?php

namespace App\Models;

use App\Enums\CallStatus;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory;

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

    public function senderId(): BelongsTo
    {
        return $this->belongsTo(SenderId::class);
    }
}

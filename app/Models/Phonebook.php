<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\OwnedByAuthUser;
use Database\Factories\PhonebookFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(OwnedByAuthUser::class)]
final class Phonebook extends Model
{
    /** @use HasFactory<PhonebookFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}

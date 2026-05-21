<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Scopes\OwnedByAuthUser;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ScopedBy(OwnedByAuthUser::class)]
final class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'description',
        'transactionable_type',
        'transactionable_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'float',
        ];
    }
}

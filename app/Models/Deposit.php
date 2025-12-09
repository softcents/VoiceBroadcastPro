<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DepositStatus;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'gateway',
        'transaction_id',
        'status',
        'meta_data',
    ];

    protected $casts = [
        'status' => DepositStatus::class,
        'meta_data' => 'array',
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

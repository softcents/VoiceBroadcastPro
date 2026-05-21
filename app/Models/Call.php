<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallInterface;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Models\Scopes\OwnedByAuthUser;
use App\Observers\CallObserver;
use App\Settings\CallingSetting;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use RuntimeException;

#[ScopedBy(OwnedByAuthUser::class)]
#[ObservedBy(CallObserver::class)]
#[Guarded(['id'])]
final class Call extends Model
{
    /** @use HasFactory<CallFactory> */
    use HasFactory;

    protected $casts = [
        'status' => CallStatus::class,
        'type' => CallType::class,
        'interface' => CallInterface::class,
        'phone_number' => E164PhoneNumberCast::class,
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

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'reference');
    }

    /**
     * Retry the call if eligible.
     */
    public function retry(): void
    {
        if ($this->canRetry) {
            $this->update([
                'status' => CallStatus::Pending,
            ]);

            $this->increment('retries');
        } else {
            throw new RuntimeException('Call cannot be retried.');
        }
    }

    protected function isRetryable(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === CallStatus::Failed,
        );
    }

    protected function canRetry(): Attribute
    {
        $callSettings = app(CallingSetting::class);

        return Attribute::make(
            get: fn () => $this->isRetryable && $this->retries < $callSettings->max_retry_attempts,
        );
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', CallStatus::Processing);
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', CallStatus::Pending);
    }

    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', CallStatus::Failed);
    }

    #[Scope]
    protected function scheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at');
    }

    #[Scope]
    protected function retryable(Builder $query): Builder
    {
        $callSettings = app(CallingSetting::class);

        return $query->where(function (Builder $q) use ($callSettings) {
            $q->where('status', CallStatus::Failed)
                ->where('retries', '<', $callSettings->max_retry_attempts);
        });
    }
}

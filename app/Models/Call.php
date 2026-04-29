<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallFromInterface;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Jobs\ProcessMarketingCall;
use App\Jobs\ProcessOtpCall;
use App\Models\Scopes\OwnedByAuthUser;
use App\Observers\CallObserver;
use App\Settings\CallingSetting;
use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;
use RuntimeException;

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
        'from_interface' => CallFromInterface::class,
        'phone_number' => E164PhoneNumberCast::class,
        'called_at' => 'datetime',
        'ringing_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'initiated_at' => 'datetime',
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

    public function events(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    /**
     * Retry the call if eligible.
     */
    public function retry(): void
    {
        if ($this->canRetry) {
            $this->update([
                'called_at' => null,
                'ringing_at' => null,
                'answered_at' => null,
                'ended_at' => null,
            ]);
            $this->increment('retries');

            $this->type === CallType::Marketing
                ? ProcessMarketingCall::dispatch($this->id)
                : ProcessOtpCall::dispatch($this->id);
        } else {
            throw new RuntimeException('Call cannot be retried.');
        }
    }

    protected function isRetryable(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, [
                CallStatus::Busy,
                CallStatus::NotAnswered,
                CallStatus::Failed,
            ], true),
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
        return $query->whereIn('status', [
            CallStatus::Initiated,
            CallStatus::Ringing,
            CallStatus::Answered,
        ]);
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
    protected function answered(Builder $query): Builder
    {
        return $query->whereNotNull('answered_at');
    }

    #[Scope]
    protected function called(Builder $query): Builder
    {
        return $query->whereNotNull('called_at');
    }

    #[Scope]
    protected function ended(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }

    #[Scope]
    protected function ringing(Builder $query): Builder
    {
        return $query->whereNotNull('ringing_at');
    }

    #[Scope]
    protected function retryable(Builder $query): Builder
    {
        $callSettings = app(CallingSetting::class);

        return $query->where(function (Builder $q) use ($callSettings) {
            $q->whereIn('status', [
                CallStatus::Busy,
                CallStatus::NotAnswered,
                CallStatus::Failed,
            ])->where('retries', '<', $callSettings->max_retry_attempts);
        });
    }
}

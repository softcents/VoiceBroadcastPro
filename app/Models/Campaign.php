<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignApproval;
use App\Enums\CampaignStatus;
use App\Models\Scopes\OwnedByAuthUser;
use App\Observers\CampaignObserver;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(OwnedByAuthUser::class)]
#[ObservedBy(CampaignObserver::class)]
#[Guarded(['id'])]
final class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $casts = [
        'status' => CampaignStatus::class,
        'approval' => CampaignApproval::class,
        'scheduled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(Caller::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Pending);
    }

    /**
     * Scope a query to only include scheduled campaigns.
     */
    #[Scope]
    protected function scheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at');
    }

    #[Scope]
    protected function notScheduled(Builder $query): Builder
    {
        return $query->whereNull('scheduled_at');
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('approval', CampaignApproval::Approved);
    }
}

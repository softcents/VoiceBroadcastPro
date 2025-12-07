<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use App\Models\Scopes\OwnedByAuthUser;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(OwnedByAuthUser::class)]
final class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'audio_id',
        'phonebook_id',
        'title',
        'description',
        'source',
        'file_path',
        'status',
        'scheduled_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function phonebook(): BelongsTo
    {
        return $this->belongsTo(Phonebook::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    protected function casts(): array
    {
        return [
            'source' => CampaignSource::class,
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
        ];
    }
}

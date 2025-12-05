<?php

namespace App\Models;

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
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
        'manual_numbers',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => CampaignSource::class,
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'manual_numbers' => 'array',
        ];
    }

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
}

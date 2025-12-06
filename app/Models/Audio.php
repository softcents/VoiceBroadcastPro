<?php

namespace App\Models;

use App\Enums\AudioApproval;
use App\Enums\AudioConversionStatus;
use App\Enums\AudioTTSStatus;
use App\Enums\AudioType;
use App\Models\Scopes\OwnedByAuthUser;
use Database\Factories\AudioFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ScopedBy(OwnedByAuthUser::class)]
class Audio extends Model
{
    /** @use HasFactory<AudioFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'tts_artist_id',
        'title',
        'description',
        'type',
        'approval',
        'message',
        'original_path',
        'converted_path',
        'duration',
        'size',
        'conversion_status',
        'conversion_error',
        'tts_status',
        'tts_error',
        'converted_at',
        'tts_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AudioType::class,
            'approval' => AudioApproval::class,
            'conversion_status' => AudioConversionStatus::class,
            'tts_status' => AudioTTSStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ttsArtist(): BelongsTo
    {
        return $this->belongsTo(TTSArtist::class);
    }

    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('approval', AudioApproval::Approved);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Audio $audio) {
            // Generate UUID if not already set
            if (empty($audio->uuid)) {
                $audio->uuid = (string)Str::uuid();
            }
        });

        static::deleting(function (Audio $audio) {
            // Delete associated audio files from storage
            if ($audio->original_path && Storage::disk('local')->exists($audio->original_path)) {
                Storage::disk('local')->delete($audio->original_path);
            }
            if ($audio->converted_path && Storage::disk('local')->exists($audio->converted_path)) {
                Storage::disk('local')->delete($audio->converted_path);
            }
        });
    }
}

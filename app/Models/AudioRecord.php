<?php

namespace App\Models;

use App\Enums\AudioRecordStatus;
use Database\Factories\AudioRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioRecord extends Model
{
    /** @use HasFactory<AudioRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'status' => AudioRecordStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(AudioFile::class, 'audio_record_id');
    }
}

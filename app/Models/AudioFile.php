<?php

namespace App\Models;

use Database\Factories\AudioFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioFile extends Model
{
    /** @use HasFactory<AudioFileFactory> */
    use HasFactory;

    protected $fillable = [
        'audio_record_id',
        'name',
        'path',
        'size',
        'mime_type',
    ];

    public function audioRecord(): BelongsTo
    {
        return $this->belongsTo(AudioRecord::class);
    }
}

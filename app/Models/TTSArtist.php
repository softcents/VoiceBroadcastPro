<?php

namespace App\Models;

use App\Enums\TTSArtistGender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TTSArtist extends Model
{
    protected $table = 'tts_artists';

    protected $fillable = [
        'tts_language_id',
        'name',
        'gender',
        'code',
        'enabled'
    ];

    protected function casts(): array
    {
        return [
            'gender' => TTSArtistGender::class,
        ];
    }

    public function ttsLanguage(): BelongsTo
    {
        return $this->belongsTo(TTSLanguage::class, 'tts_language_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TTSLanguage extends Model
{
    protected $table = 'tts_languages';

    protected $fillable = [
        'name',
        'code',
        'engine',
        'enabled'
    ];

    public function ttsArtists(): HasMany
    {
        return $this->hasMany(TTSArtist::class, 'tts_language_id');
    }
}

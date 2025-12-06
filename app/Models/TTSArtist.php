<?php

namespace App\Models;

use App\Enums\TTSArtistGender;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TTSArtist extends Model
{
    use HasFactory;

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

    #[Scope]
    protected function enabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    #[Scope]
    protected function male(Builder $query): Builder
    {
        return $query->where('gender', TTSArtistGender::Male);
    }

    #[Scope]
    protected function female(Builder $query): Builder
    {
        return $query->where('gender', TTSArtistGender::Female);
    }

    #[Scope]
    protected function neutral(Builder $query): Builder
    {
        return $query->where('gender', TTSArtistGender::Neutral);
    }
}

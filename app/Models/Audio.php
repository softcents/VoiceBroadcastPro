<?php

namespace App\Models;

use App\Enums\AudioApproval;
use App\Enums\AudioArtist;
use App\Enums\AudioGender;
use App\Enums\AudioLanguage;
use App\Enums\AudioType;
use Database\Factories\AudioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audio extends Model
{
    /** @use HasFactory<AudioFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'approval',
        'message',
        'language',
        'gender',
        'artist',
        'original_path',
        'converted_path',
        'duration',
        'size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'type' => AudioType::class,
            'approval' => AudioApproval::class,
            'language' => AudioLanguage::class,
            'gender' => AudioGender::class,
            'artist' => AudioArtist::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

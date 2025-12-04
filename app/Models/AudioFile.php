<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudioFile extends Model
{
    /** @use HasFactory<\Database\Factories\AudioFileFactory> */
    use HasFactory;
    protected $fillable = [
        'audio_record_id',
        'name',
        'path',
        'size',
        'mime_type',
    ];
}

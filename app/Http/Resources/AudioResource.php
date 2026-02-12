<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Audio
 */
final class AudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'approval' => $this->approval,
            'message' => $this->message,
            'tts_artist' => $this->whenLoaded('ttsArtist'),
            'original_url' => $this->original_path ? Storage::temporaryUrl($this->original_path, now()->addMinutes(15)) : null,
            'converted_url' => $this->converted_path ? Storage::temporaryUrl($this->converted_path, now()->addMinutes(15)) : null,
            'duration' => $this->duration,
            'size' => $this->size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

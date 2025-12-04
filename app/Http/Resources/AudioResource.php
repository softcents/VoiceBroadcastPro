<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioResource extends JsonResource
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
            'language' => $this->language,
            'gender' => $this->gender,
            'artist' => $this->artist,
            'original_path' => $this->original_path ? \Illuminate\Support\Facades\Storage::url($this->original_path) : null,
            'converted_path' => $this->converted_path ? \Illuminate\Support\Facades\Storage::url($this->converted_path) : null,
            'duration' => $this->duration,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

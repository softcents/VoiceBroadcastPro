<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'source' => $this->source,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'audio' => new AudioResource($this->whenLoaded('audio')),
            'phonebook' => new PhonebookResource($this->whenLoaded('phonebook')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'calls_success_count' => $this->whenCounted('calls_success_count'),
            'calls_failed_count' => $this->whenCounted('calls_failed_count'),
            'calls_rejected_count' => $this->whenCounted('calls_rejected_count'),
            'calls_not_answered_count' => $this->whenCounted('calls_not_answered_count'),
        ];
    }
}

<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'audio_id' => ['sometimes', 'exists:audio,id'],
            'phonebook_id' => ['nullable', 'required_if:source,' . CampaignSource::Phonebook->value, 'exists:phonebooks,id'],
            'source' => ['sometimes', Rule::enum(CampaignSource::class)],
            'status' => ['sometimes', Rule::enum(CampaignStatus::class)],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'The title of the campaign.',
                'example' => 'Updated Summer Sale Campaign',
            ],
            'description' => [
                'description' => 'A brief description of the campaign.',
                'example' => 'Updated description.',
            ],
            'audio_id' => [
                'description' => 'The ID of the audio file to be used.',
                'example' => 1,
            ],
            'phonebook_id' => [
                'description' => 'The ID of the phonebook (required if source is phonebook).',
                'example' => 5,
            ],
            'source' => [
                'description' => 'The source of contacts for the campaign.',
                'example' => CampaignSource::Phonebook->value,
            ],
            'status' => [
                'description' => 'The status of the campaign.',
                'example' => CampaignStatus::Pending->value,
            ],
            'scheduled_at' => [
                'description' => 'The scheduled time for the campaign to start.',
                'example' => '2025-12-26 10:00:00',
            ],
        ];
    }
}

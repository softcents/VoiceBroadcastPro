<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use App\Enums\CampaignSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StoreCampaignRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'audio_id' => ['required', 'exists:audio,id'],
            'source' => ['required', Rule::enum(CampaignSource::class)],

            'phonebook_id' => [
                Rule::requiredIf(function () {
                    return $this->input('source') === CampaignSource::Phonebook->value;
                }),
                Rule::prohibitedIf(function () {
                    return $this->input('source') !== CampaignSource::Phonebook->value;
                }),
                'nullable',
                'integer',
                Rule::exists('phonebooks', 'id')->where('user_id', Auth::id()),
            ],

            'phone_numbers' => [
                Rule::requiredIf(function () {
                    return $this->input('source') === CampaignSource::Manual->value;
                }),
                Rule::prohibitedIf(function () {
                    return $this->input('source') !== CampaignSource::Manual->value;
                }),
                'nullable',
                'array',
                'min:1',
            ],
            'phone_numbers.*' => ['string', 'phone:E.164,BD'],

            'file' => [
                Rule::requiredIf(function () {
                    return $this->input('source') === CampaignSource::Import->value;
                }),
                Rule::prohibitedIf(function () {
                    return $this->input('source') !== CampaignSource::Import->value;
                }),
                'nullable',
                'file',
                'mimes:csv',
                'max:2048',
            ],
            'scheduled_at' => ['nullable', 'date', 'after:5 minutes'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'The title of the campaign.',
                'example' => 'Summer Sale Campaign',
            ],
            'description' => [
                'description' => 'A brief description of the campaign.',
                'example' => 'Promotional calls for summer discounts.',
            ],
            'audio_id' => [
                'description' => 'The ID of the audio file to be used.',
                'example' => 1,
            ],
            'source' => [
                'description' => 'The source of contacts for the campaign.',
                'example' => CampaignSource::Phonebook->value,
            ],

            'phonebook_id' => [
                'description' => 'The ID of the phonebook (required if source is phonebook).',
                'example' => 5,
            ],
            'file' => [
                'description' => 'A CSV file containing phone numbers (required if source is import).',
            ],
            'scheduled_at' => [
                'description' => 'The scheduled time for the campaign to start. (min: 5 minutes from now)',
                'example' => '2025-12-25 10:00:00',
            ],
        ];
    }
}

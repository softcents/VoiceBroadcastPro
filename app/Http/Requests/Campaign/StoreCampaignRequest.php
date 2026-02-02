<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use App\Rules\EnsureUserHasSufficientBalanceForCampaign;
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
            'audio_id' => [
                'required',
                'exists:audio,id',
                new EnsureUserHasSufficientBalanceForCampaign($this->integer('phonebook_id')),
            ],
            'phonebook_id' => [
                'required',
                'integer',
                Rule::exists('phonebooks', 'id')
                    ->where('user_id', Auth::id()),
            ],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
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
            'phonebook_id' => [
                'description' => 'The ID of the phonebook associated with the campaign.',
                'example' => 5,
            ],
            'scheduled_at' => [
                'description' => 'The scheduled time for the campaign to start.',
                'example' => '2025-12-25 10:00:00',
            ],
        ];
    }
}

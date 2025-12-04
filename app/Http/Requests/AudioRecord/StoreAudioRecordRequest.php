<?php

namespace App\Http\Requests\AudioRecord;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAudioRecordRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:mp3', 'max:2048'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => [
                'example' => 'My First Audio Recording',
            ],
            'files' => [
                'example' => ['audio1.mp3', 'audio2.wav'],
            ],
            'files.*' => [
                'description' => 'An audio file in mp3 format.',
                'example' => ['audio1.mp3', 'audio2.wav'],
            ]
        ];
    }
}

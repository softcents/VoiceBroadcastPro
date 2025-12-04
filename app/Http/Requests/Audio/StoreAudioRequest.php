<?php

namespace App\Http\Requests\Audio;

use App\Enums\AudioArtist;
use App\Enums\AudioGender;
use App\Enums\AudioLanguage;
use App\Enums\AudioType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAudioRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(AudioType::class)],
            'message' => ['required_if:type,tts', 'prohibited_if:type,record', 'nullable', 'string'],
            'file' => ['required_if:type,record', 'prohibited_if:type,tts', 'nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:10240'], // 10MB max
            'language' => ['required_if:type,tts', 'prohibited_if:type,record', 'nullable', Rule::enum(AudioLanguage::class)],
            'gender' => ['required_if:type,tts', 'prohibited_if:type,record', 'nullable', Rule::enum(AudioGender::class)],
            'artist' => ['required_if:type,tts', 'prohibited_if:type,record', 'nullable', Rule::enum(AudioArtist::class)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'artist' => [
                'description' => 'The artist/voice name (required if type is tts).',
                'example' => 'bn-BD-PradeepNeural',
            ],
            'description' => [
                'description' => 'The description of the audio.',
                'example' => 'Greeting for new customers.',
            ],
            'file' => [
                'description' => 'The audio file to upload (mp3, wav, ogg, m4a). Required if type is record.',
            ],
            'gender' => [
                'description' => 'The gender of the voice (required if type is tts).',
                'example' => 'Male',
            ],
            'language' => [
                'description' => 'The language code (required if type is tts).',
                'example' => 'bn-BD',
            ],
            'message' => [
                'description' => 'The message for TTS generation (required if type is tts).',
                'example' => 'Hello, welcome to our service.',
            ],
            'title' => [
                'description' => 'The title of the audio.',
                'example' => 'Welcome Message',
            ],
            'type' => [
                'description' => 'The type of audio (tts or record).',
                'example' => 'tts',
            ],
        ];
    }
}

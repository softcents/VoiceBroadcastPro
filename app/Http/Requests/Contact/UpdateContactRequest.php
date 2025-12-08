<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContactRequest extends FormRequest
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
            'phonebook_id' => ['sometimes', 'integer', 'exists:phonebooks,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => [
                'sometimes',
                'string',
                'phone',
                Rule::unique('contacts', 'phone_number')
                    ->where('phonebook_id', $this->route('contact')?->phonebook_id)
                    ->ignore($this->route('contact')?->id)
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'phonebook_id' => [
                'description' => 'The ID of the phonebook. (Optional)',
            ],
            'first_name' => [
                'description' => 'The first name of the contact. (Optional)',
                'example' => 'John',
            ],
            'last_name' => [
                'description' => 'The last name of the contact. (Optional)',
                'example' => 'Doe',
            ],
            'phone_number' => [
                'description' => 'The phone number of the contact. (Optional)',
                'example' => '+8801XXXXXXXXX',
            ],
        ];
    }
}

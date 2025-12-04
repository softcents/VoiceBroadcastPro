<?php

namespace App\Http\Requests\Contact;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
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
            'contact_group_id' => ['sometimes', 'integer', 'exists:contact_groups,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'string', 'phone:E.164,BD'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'contact_group_id' => [
                'description' => 'The ID of the contact group.',
            ],
            'first_name' => [
                'description' => 'The first name of the contact.',
                'example' => 'John'
            ],
            'last_name' => [
                'description' => 'The last name of the contact.',
                'example' => 'Doe'
            ],
            'phone_number' => [
                'description' => 'The phone number of the contact.',
                'example' => '+8801XXXXXXXXX',
            ],
        ];
    }
}

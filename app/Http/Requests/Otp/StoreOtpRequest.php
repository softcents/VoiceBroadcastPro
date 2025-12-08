<?php

namespace App\Http\Requests\Otp;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOtpRequest extends FormRequest
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
            'caller_id' => [
                'required',
                'exists:callers,id,enabled,1',
                // The caller must belong to the authenticated user by checking whereHas - because its belongsToMany relationship,
            ],
            'code' => ['required', 'integer', 'max_digits:10'],
            'recipient' => ['required', 'string', 'max:15'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CustomerVerifyOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'digits:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email field is required.',
            'email.string' => 'Email must be a string.',
            'email.email' => 'Please enter a valid email address.',
            'code.required' => 'Code field is required.',
            'code.digits' => 'Code must be 4 digits.',
        ];
    }
}

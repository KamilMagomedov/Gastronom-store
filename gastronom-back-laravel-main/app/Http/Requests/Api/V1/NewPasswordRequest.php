<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class NewPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'digits:4'],
            'password' => ['required', 'max:190', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'token.required' => 'Token field is required.',
            'token.digits' => 'Token must be 4 digits.',
            'password.required' => 'Password field is required.',
            'password.max' => 'Password must not exceed 190 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'max:190'],
        ];
    }

    public function messages()
    {
        return [
            'password.required' => 'The password field is required.',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SupportTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SupportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'theme' => ['required', new Enum(SupportTheme::class)],
            'order_id' => ['nullable', 'integer', 'min:1'],
            'message' => ['required', 'max:1500'],
        ];
    }

    public function messages(): array
    {
        return [
            'theme.required' => 'Theme field is required.',
            'theme.enum' => 'Selected theme is invalid.',
            'order_id.integer' => 'Order ID must be an integer.',
            'order_id.min' => 'Order ID must be a positive number.',
            'message.required' => 'Message field is required.',
            'message.max' => 'Message may not be greater than 1500 characters.',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OrderRepeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID field is required.',
            'order_id.integer' => 'Order ID must be an integer.',
            'order_id.exists' => 'Selected order is invalid.',
        ];
    }
}

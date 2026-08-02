<?php

namespace App\Http\Requests\Api;

use App\DTO\Order\CancelOrderData;
use Illuminate\Foundation\Http\FormRequest;

class OrderCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customers')->check();
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.string' => 'Reason must be a string.',
            'reason.max' => 'Reason may not be greater than 500 characters.',
        ];
    }

    public function toDto(): CancelOrderData
    {
        return new CancelOrderData(
            reason: $this->validated('reason')
        );
    }
}

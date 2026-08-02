<?php

namespace App\Http\Requests\Api;

use App\DTO\Order\DeliveryCalculateData;
use Illuminate\Foundation\Http\FormRequest;

class DeliveryCalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customers')->check();
    }

    public function rules(): array
    {
        return [
            'delivery_method' => 'required|integer|exists:delivery_methods,id',
            'cart_total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_method.required' => 'Please select a delivery method',
            'delivery_method.integer' => 'Invalid delivery method',
            'delivery_method.exists' => 'Selected delivery method does not exist',
            'cart_total.required' => 'Please provide cart total',
            'cart_total.numeric' => 'Cart total must be a number',
            'cart_total.min' => 'Cart total cannot be negative',
        ];
    }

    public function toDto(): DeliveryCalculateData
    {
        return new DeliveryCalculateData(
            deliveryMethod: $this->validated('delivery_method'),
            cartTotal: (float) $this->validated('cart_total')
        );
    }
}

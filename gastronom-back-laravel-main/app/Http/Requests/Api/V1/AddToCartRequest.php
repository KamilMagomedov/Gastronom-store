<?php

namespace App\Http\Requests\Api\V1;

use App\DTO\Cart\AddToCartData;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'The product ID field is required.',
            'product_id.exists' => 'The selected product is invalid.',
            'quantity.required' => 'The quantity field is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.max' => 'The quantity may not be greater than 100.',
            'session_id.string' => 'Session ID must be a string.',
            'session_id.max' => 'Session ID may not be greater than 50 characters.',
        ];
    }

    /**
     * Get the DTO representation of the request data.
     */
    public function toDto(): AddToCartData
    {
        return new AddToCartData(
            productId: $this->validated('product_id'),
            quantity: $this->validated('quantity'),
            sessionId: $this->validated('session_id')
        );
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:190'],
            'email' => ['sometimes', 'email', 'unique:customers,email,'.$this->user()->id],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone' => ['sometimes', 'string', 'regex:/^(\+7|7|8)[0-9]{10}$/'],
            'delivery_street' => ['sometimes', 'string', 'max:190'],
            'delivery_city' => ['sometimes', 'string', 'max:100'],
            'delivery_apartment' => ['sometimes', 'string', 'max:10'],
            'delivery_postal_code' => ['sometimes', 'string', 'max:20'],
            'delivery_building' => ['sometimes', 'string', 'max:10'],
            'delivery_entrance' => ['sometimes', 'string', 'max:10'],
            'delivery_floor' => ['sometimes', 'string', 'max:10'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name must not exceed 190 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'phone.string' => 'Phone must be a string.',
            'phone.regex' => 'Please enter a valid Russian phone number (e.g., +79281234567, 79281234567, or 89281234567).',
            'delivery_street.string' => 'Delivery street must be a string.',
            'delivery_street.max' => 'Delivery street must not exceed 190 characters.',
            'delivery_city.string' => 'Delivery city must be a string.',
            'delivery_city.max' => 'Delivery city must not exceed 100 characters.',
            'delivery_apartment.string' => 'Delivery apartment must be a string.',
            'delivery_apartment.max' => 'Delivery apartment must not exceed 10 characters.',
            'delivery_postal_code.string' => 'Delivery postal code must be a string.',
            'delivery_postal_code.max' => 'Delivery postal code must not exceed 20 characters.',
            'delivery_building.string' => 'Delivery building must be a string.',
            'delivery_building.max' => 'Delivery building must not exceed 10 characters.',
            'delivery_entrance.string' => 'Delivery entrance must be a string.',
            'delivery_entrance.max' => 'Delivery entrance must not exceed 10 characters.',
            'delivery_floor.string' => 'Delivery floor must be a string.',
            'delivery_floor.max' => 'Delivery floor must not exceed 10 characters.',
        ];
    }
}

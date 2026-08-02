<?php

namespace App\Http\Requests\Api;

use App\DTO\Order\CreateOrderData;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_method' => [
                'required',
                'integer',
                'exists:delivery_methods,id',
            ],
            'delivery_phone' => [
                'required_if:delivery_method,2,3',
                'string',
                'max:20',
                'regex:/^\+?\d{10,15}$/',
            ],
            'delivery_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'delivery_street' => [
                'required_if:delivery_method,2,3',
                'string',
                'max:190',
            ],
            'delivery_city' => [
                'required_if:delivery_method,2,3',
                'string',
                'max:100',
            ],
            'delivery_apartment' => [
                'nullable',
                'string',
                'max:20',
            ],
            'delivery_postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'delivery_latitude' => [
                'nullable',
                'decimal:8',
                'between:-90,90',
            ],
            'delivery_longitude' => [
                'nullable',
                'decimal:8',
                'between:-180,180',
            ],
            'delivery_building' => [
                'nullable',
                'string',
                'max:20',
            ],
            'delivery_entrance' => [
                'nullable',
                'string',
                'max:10',
            ],
            'delivery_floor' => [
                'nullable',
                'string',
                'max:10',
            ],
            'payment_method' => [
                'required',
                'integer',
                'exists:payment_methods,id',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_method.required' => 'Please select a delivery method',
            'delivery_method.in' => 'Invalid delivery method',
            'delivery_phone.required_if' => 'Please provide a delivery phone',
            'delivery_phone.max' => 'Phone number must not exceed 20 characters',
            'delivery_phone.regex' => 'Please enter a valid phone number',
            'delivery_street.required_if' => 'Please provide a street',
            'delivery_street.max' => 'Street name must not exceed 190 characters',
            'delivery_city.required_if' => 'Please provide a city',
            'delivery_city.max' => 'City name must not exceed 100 characters',
            'delivery_apartment.max' => 'Apartment number must not exceed 20 characters',
            'delivery_postal_code.max' => 'Postal code must not exceed 20 characters',
            'delivery_latitude.decimal' => 'Latitude must be a valid decimal number',
            'delivery_latitude.between' => 'Latitude must be between -90 and 90',
            'delivery_longitude.decimal' => 'Longitude must be a valid decimal number',
            'delivery_longitude.between' => 'Longitude must be between -180 and 180',
            'delivery_building.max' => 'Building number must not exceed 20 characters',
            'delivery_entrance.max' => 'Entrance number must not exceed 10 characters',
            'delivery_floor.max' => 'Floor number must not exceed 10 characters',
            'payment_method.required' => 'Please select a payment method',
            'payment_method.in' => 'Invalid payment method',
            'notes.max' => 'Notes must not exceed 1000 characters',
            'delivery_notes.max' => 'Delivery notes must not exceed 1000 characters',
        ];
    }

    public function toDto(): CreateOrderData
    {
        return new CreateOrderData(
            deliveryMethod: $this->validated('delivery_method'),
            paymentMethod: $this->validated('payment_method'),
            deliveryPhone: $this->validated('delivery_phone'),
            deliveryNotes: $this->validated('delivery_notes'),
            deliveryStreet: $this->validated('delivery_street'),
            deliveryCity: $this->validated('delivery_city'),
            deliveryApartment: $this->validated('delivery_apartment'),
            deliveryPostalCode: $this->validated('delivery_postal_code'),
            deliveryLatitude: $this->validated('delivery_latitude'),
            deliveryLongitude: $this->validated('delivery_longitude'),
            deliveryBuilding: $this->validated('delivery_building'),
            deliveryEntrance: $this->validated('delivery_entrance'),
            deliveryFloor: $this->validated('delivery_floor'),
            notes: $this->validated('notes')
        );
    }
}

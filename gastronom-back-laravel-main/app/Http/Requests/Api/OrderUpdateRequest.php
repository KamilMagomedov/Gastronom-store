<?php

namespace App\Http\Requests\Api;

use App\DTO\Order\UpdateOrderData;
use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customers')->check();
    }

    public function rules(): array
    {
        return [
            'delivery_phone' => 'nullable|string|max:20',
            'delivery_notes' => 'nullable|string|max:1000',
            'delivery_street' => 'nullable|string|max:190',
            'delivery_city' => 'nullable|string|max:100',
            'delivery_apartment' => 'nullable|string|max:20',
            'delivery_postal_code' => 'nullable|string|max:20',
            'delivery_latitude' => 'nullable|decimal:8|between:-90,90',
            'delivery_longitude' => 'nullable|decimal:8|between:-180,180',
            'delivery_building' => 'nullable|string|max:20',
            'delivery_entrance' => 'nullable|string|max:10',
            'delivery_floor' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_phone.max' => 'Phone number must not exceed 20 characters',
            'delivery_notes.max' => 'Delivery notes must not exceed 1000 characters',
            'delivery_street.max' => 'Street name must not exceed 190 characters',
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
            'notes.max' => 'Notes must not exceed 1000 characters',
        ];
    }

    public function toDto(): UpdateOrderData
    {
        return new UpdateOrderData(
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

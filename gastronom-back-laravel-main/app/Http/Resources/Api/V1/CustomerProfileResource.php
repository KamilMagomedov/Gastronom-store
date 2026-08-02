<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'phone' => $this->phone,
            'delivery_street' => $this->delivery_street,
            'delivery_city' => $this->delivery_city,
            'delivery_apartment' => $this->delivery_apartment,
            'delivery_postal_code' => $this->delivery_postal_code,
            'delivery_building' => $this->delivery_building,
            'delivery_entrance' => $this->delivery_entrance,
            'delivery_floor' => $this->delivery_floor,
            'created_at' => $this->created_at,
        ];
    }
}

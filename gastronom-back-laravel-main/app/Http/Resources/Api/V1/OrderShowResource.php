<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $products = $this->orderItems
            ->filter(fn ($orderItem) => $orderItem->product)
            ->map(function ($orderItem) use ($request) {
                return [
                    ...ProductItemResource::make($orderItem->product)->resolve($request),

                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'total_price' => $orderItem->total_price,
                ];
            })
            ->values();

        $deliveryAddress = collect([
            $this->delivery_city,
            $this->delivery_street,
            $this->delivery_building
                ? 'д. '.$this->delivery_building
                : null,
            $this->delivery_apartment
                ? 'кв. '.$this->delivery_apartment
                : null,
            $this->delivery_entrance
                ? 'подъезд '.$this->delivery_entrance
                : null,
            $this->delivery_floor
                ? 'этаж '.$this->delivery_floor
                : null,
            $this->delivery_postal_code,
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->implode(', ');

        return [
            'id' => $this->id,

            'customer' => CustomerUnitResource::make(
                $this->whenLoaded('customer')
            ),

            'total_amount' => $this->total_amount,
            'shipping_amount' => $this->shipping_amount,

            'delivery_method' => DeliveryMethodResource::make(
                $this->deliveryMethod
            ),

            'delivery_cost' => $this->delivery_cost,

            'delivery_address' => $deliveryAddress ?: null,

            'delivery_city' => $this->delivery_city,
            'delivery_street' => $this->delivery_street,
            'delivery_building' => $this->delivery_building,
            'delivery_apartment' => $this->delivery_apartment,
            'delivery_entrance' => $this->delivery_entrance,
            'delivery_floor' => $this->delivery_floor,
            'delivery_postal_code' => $this->delivery_postal_code,

            'delivery_phone' => $this->delivery_phone,
            'delivery_notes' => $this->delivery_notes,

            'payment_method' => PaymentMethodResource::make(
                $this->paymentMethod
            ),

            'status' => $this->status,
            'payment_status' => $this->payment_status,

            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,

            'products' => $products->all(),
        ];
    }
}

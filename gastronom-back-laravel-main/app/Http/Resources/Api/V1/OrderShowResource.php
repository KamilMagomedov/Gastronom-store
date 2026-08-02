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
        $products = $this->orderItems->map(fn ($orderItem) => $orderItem->product)->filter();

        return [
            'id' => $this->id,
            'customer' => CustomerUnitResource::make($this->whenLoaded('customer')),
            'total_amount' => $this->total_amount,
            'shipping_amount' => $this->shipping_amount,
            'delivery_method' => DeliveryMethodResource::make($this->deliveryMethod),
            'delivery_cost' => $this->delivery_cost,
            'delivery_address' => $this->delivery_address,
            'delivery_phone' => $this->delivery_phone,
            'payment_method' => PaymentMethodResource::make($this->paymentMethod),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'products' => ProductItemResource::collection($products),
        ];
    }
}

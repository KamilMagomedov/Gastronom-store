<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $orderItems = $this->orderItems;
        $firstProduct = $orderItems?->first()?->product;

        return [
            'id' => $this->id,
            'total_amount' => $this->total_amount,
            'payment_method' => PaymentMethodResource::make($this->paymentMethod),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'order_summary' => [
                'image' => $firstProduct?->getFirstMediaUrl('images'),
                'product_names' => $orderItems?->pluck('product_name')->implode(', ') ?? '',
                'items_count' => $orderItems?->sum('quantity') ?? 0,
                'total_price' => $this->total_amount,
            ],
        ];
    }
}

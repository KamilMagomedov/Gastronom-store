<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRepeatItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'product_image' => $product->getFirstMediaUrl('images'),
            'requested_quantity' => $this->quantity,
            'available_quantity' => $product->stock_quantity,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'unit' => $product->unit,
            'is_available' => $product->isAvailableForRepeat($this->quantity),
            'shortage' => $product->getShortage($this->quantity),
        ];
    }
}

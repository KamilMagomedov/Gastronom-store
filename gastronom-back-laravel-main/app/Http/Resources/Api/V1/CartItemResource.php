<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'quantity' => $this->resource->quantity,
            'price' => $this->resource->price,
            'total' => $this->resource->total,
            'product' => ProductResource::make($this->resource->product),
        ];
    }
}

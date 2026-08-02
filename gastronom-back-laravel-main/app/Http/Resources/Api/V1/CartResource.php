<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_amount' => $this->resource->total_amount,
            'total_items' => $this->resource->total_items,
            'expires_at' => $this->resource->expires_at,
            'session_id' => $this->resource->session_id,
            'items' => CartItemResource::collection($this->resource->items),
        ];
    }
}

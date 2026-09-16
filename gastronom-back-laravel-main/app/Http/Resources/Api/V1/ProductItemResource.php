<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'price' => $this->resource->price,
            'old_price' => $this->resource->old_price,
            'sku' => $this->resource->sku,
            'unit' => $this->resource->unit,
            'image' => $this->resource->getFirstMediaUrl('images'),

            'in_stock' => $this->resource->in_stock,
            'stock_quantity' => $this->resource->stock_quantity,
            'is_active' => $this->resource->is_active,

            'category' => CategoryItemResource::make(
                $this->resource->category
            ),
        ];
    }
}

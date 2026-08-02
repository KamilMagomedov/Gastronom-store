<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
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
            'rating' => $this->resource->rating,
            'comment' => $this->resource->comment,
            'created_at' => $this->resource->created_at?->format('Y-m-d H:i:s'),
            'customer' => CustomerUnitResource::make($this->resource->customer),
        ];
    }
}

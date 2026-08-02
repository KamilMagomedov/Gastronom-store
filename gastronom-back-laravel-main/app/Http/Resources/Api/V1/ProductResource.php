<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'old_price' => $this->resource->old_price,
            'sku' => $this->resource->sku,
            'unit' => $this->resource->unit,
            'images' => $this->resource->getMedia('images')->map(fn ($media) => $media->getUrl())->toArray(),
            'rating' => [
                'average' => $this->resource->getAverageRating(),
                'count' => $this->resource->getReviewsCount(),
            ],
        ];
    }
}

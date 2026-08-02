<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'product_id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'in_stock' => $this->in_stock,
            'stock_quantity' => $this->stock_quantity,
            'is_active' => $this->is_active,
            'available' => $this->isAvailable(),
        ];

        $quantity = $request->integer('quantity');

        if ($quantity > 0) {
            $data['requested_quantity'] = $quantity;
            $data['is_enough'] = ! $this->isNotEnoughStockAvailable($quantity);
            $data['shortage'] = $this->getShortage($quantity);
            $data['status'] = $this->getAvailabilityStatus($quantity);
        }

        return $data;
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRepeatUnavailableItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->order_item_id,
            'product_id' => $this->id,
            'product_name' => $this->name,
            'product_sku' => $this->sku ?? '',
            'requested_quantity' => $this->requested_quantity,
            'reason' => $this->availability_reason,
        ];
    }
}

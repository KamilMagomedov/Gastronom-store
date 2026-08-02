<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRepeatCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $checkResult = $this->resource;

        return [
            'order_id' => $checkResult['order']->id,
            'available_items' => OrderRepeatItemResource::collection($checkResult['available_items']),
            'unavailable_items' => OrderRepeatUnavailableItemResource::collection($checkResult['unavailable_items']),
            'can_repeat' => $checkResult['can_repeat'],
            'total_available_items' => $checkResult['available_items']->count(),
            'total_unavailable_items' => $checkResult['unavailable_items']->count(),
        ];
    }
}

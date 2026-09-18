<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->name,
            'description' => $this->description,

            'is_online' => $this->acquirer_id !== null || $this->gateway !== null,
            'acquirer_code' => $this->acquirer_code,
            'payment_method_type' => $this->payment_method_type,
        ];
    }
}

<?php

namespace App\DTO\Order;

readonly class DeliveryCalculateData
{
    public function __construct(
        public int $deliveryMethod,
        public float $cartTotal
    ) {}
}

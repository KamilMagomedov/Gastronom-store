<?php

namespace App\DTO\Order;

readonly class CancelOrderData
{
    public function __construct(
        public ?string $reason = null
    ) {}
}

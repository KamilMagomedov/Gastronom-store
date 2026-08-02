<?php

namespace App\DTO\Cart;

readonly class AddToCartData
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public ?string $sessionId = null
    ) {}
}

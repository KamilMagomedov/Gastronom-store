<?php

namespace App\DTO\Cart;

readonly class UpdateCartItemData
{
    public function __construct(
        public int $quantity
    ) {}
}

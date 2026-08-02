<?php

namespace App\DTO\Cart;

readonly class GetCartData
{
    public function __construct(
        public ?string $sessionId = null
    ) {}
}

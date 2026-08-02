<?php

namespace App\Exceptions\Cart;

use Exception;

class CartNotFoundException extends Exception
{
    public function __construct(string $message = 'Cart not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

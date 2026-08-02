<?php

namespace App\Exceptions\Cart;

use Exception;

class ProductNotFoundInCartException extends Exception
{
    public function __construct(string $message = 'Product not found in cart', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

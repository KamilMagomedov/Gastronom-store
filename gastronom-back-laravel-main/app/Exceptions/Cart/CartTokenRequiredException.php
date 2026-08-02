<?php

namespace App\Exceptions\Cart;

use Exception;

class CartTokenRequiredException extends Exception
{
    public function __construct(string $message = 'Cart token is required', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

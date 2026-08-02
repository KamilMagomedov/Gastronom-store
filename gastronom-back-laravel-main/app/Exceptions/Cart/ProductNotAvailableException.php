<?php

namespace App\Exceptions\Cart;

use Exception;

class ProductNotAvailableException extends Exception
{
    public function __construct(string $message = 'Товар недоступен', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

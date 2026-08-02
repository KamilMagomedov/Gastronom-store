<?php

namespace App\Exceptions\Cart;

use Exception;

class NotEnoughStockException extends Exception
{
    public function __construct(string $message = 'Недостаточно товара в наличии.', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}

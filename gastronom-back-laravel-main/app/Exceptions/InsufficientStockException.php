<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        string $message = 'Недостаточно товара на складе',
        private array $items = [],
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}

<?php

namespace App\Exceptions;

use Exception;

class PriceChangedException extends Exception
{
    public function __construct(
        string $message = 'Цена товара изменилась',
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
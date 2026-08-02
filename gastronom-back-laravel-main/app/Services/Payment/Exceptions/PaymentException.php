<?php

namespace App\Services\Payment\Exceptions;

class PaymentException extends \RuntimeException
{
    public function __construct(string $message = 'Payment processing error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

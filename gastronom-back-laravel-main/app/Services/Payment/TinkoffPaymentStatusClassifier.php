<?php

namespace App\Services\Payment;

class TinkoffPaymentStatusClassifier
{
    public function classify(string $status): string
    {
        return match ($status) {
            'CONFIRMED' => 'confirmed',

            'REJECTED',
            'CANCELED',
            'REVERSED',
            'AUTH_FAIL' => 'failed',

            'NEW',
            'FORM_SHOWED',
            'AUTHORIZING',
            '3DS_CHECKING',
            '3DS_CHECKED',
            'AUTHORIZED',
            'CONFIRMING' => 'pending',

            default => 'unknown',
        };
    }
}

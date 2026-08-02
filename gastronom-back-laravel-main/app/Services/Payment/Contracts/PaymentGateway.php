<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Models\Transaction;

interface PaymentGateway
{
    public function createPayment(Order $order): array;

    public function handleWebhook(array $payload): Transaction;

    public function getGatewayName(): string;

    public function isAvailable(): bool;
}

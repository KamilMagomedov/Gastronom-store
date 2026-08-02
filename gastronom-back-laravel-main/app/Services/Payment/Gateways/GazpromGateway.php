<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;

class GazpromGateway extends BaseAcquirerGateway implements PaymentGateway
{
    protected string $gatewayName = 'gazprom';

    protected string $apiUrl = 'https://pay.gazprombank.ru/api/v1';

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    protected function getCreatePaymentEndpoint(): string
    {
        return $this->apiUrl.'/orders';
    }

    protected function buildCreatePaymentPayload(Order $order): array
    {
        return [
            'merchantId' => $this->config['merchant_id'] ?? '',
            'orderNumber' => (string) $order->id,
            'amount' => (int) ($order->total_amount * 100),
            'currency' => 'RUB',
            'description' => 'Заказ №'.$order->id,
            'callbackUrl' => url('/payment/'.$order->id.'/callback'),
            'failUrl' => url('/payment/'.$order->id.'/callback'),
            'metadata' => [
                'order_id' => (string) $order->id,
                'customer_id' => (string) $order->customer_id,
            ],
        ];
    }

    protected function parseCreatePaymentResponse(array $response, Order $order): array
    {
        return [
            'payment_url' => $response['paymentUrl'] ?? $response['redirectUrl'] ?? $response['url'] ?? null,
            'gateway_transaction_id' => $response['transactionId'] ?? $response['id'] ?? null,
            'payment_method_type' => 'bank_card',
        ];
    }

    protected function authenticateRequest($http): void
    {
        if (! empty($this->config['api_key'])) {
            $http->withHeader('Authorization', 'Bearer '.$this->config['api_key']);
        }
    }
}

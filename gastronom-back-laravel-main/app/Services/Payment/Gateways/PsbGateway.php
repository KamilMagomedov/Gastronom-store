<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;

class PsbGateway extends BaseAcquirerGateway implements PaymentGateway
{
    protected string $gatewayName = 'psb';

    protected string $apiUrl = 'https://ecommerce.psb.ru/api';

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    protected function getCreatePaymentEndpoint(): string
    {
        return $this->apiUrl.'/payments';
    }

    protected function buildCreatePaymentPayload(Order $order): array
    {
        return [
            'merchantId' => $this->config['merchant_id'] ?? '',
            'orderId' => (string) $order->id,
            'amount' => (int) ($order->total_amount * 100),
            'currency' => 'RUB',
            'description' => 'Заказ №'.$order->id,
            'returnUrl' => url('/payment/'.$order->id.'/callback'),
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
            'payment_url' => $response['paymentUrl'] ?? $response['redirectUrl'] ?? $response['confirmationUrl'] ?? null,
            'gateway_transaction_id' => $response['transactionId'] ?? $response['id'] ?? null,
            'payment_method_type' => 'bank_card',
        ];
    }

    protected function authenticateRequest($http): void
    {
        if (! empty($this->config['secret_key'])) {
            $http->withHeader('X-Signature', hash('sha256', $this->config['secret_key']));
        }
    }
}

<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;

class AlfaGateway extends BaseAcquirerGateway implements PaymentGateway
{
    protected string $gatewayName = 'alfa';

    protected string $apiUrl = 'https://pay.alfabank.ru/payment/rest';

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    protected function getCreatePaymentEndpoint(): string
    {
        return $this->apiUrl.'/register.do';
    }

    protected function buildCreatePaymentPayload(Order $order): array
    {
        return [
            'userName' => $this->config['login'] ?? $this->config['merchant_id'] ?? '',
            'password' => $this->config['password'] ?? $this->config['api_key'] ?? '',
            'orderNumber' => (string) $order->id,
            'amount' => (int) ($order->total_amount * 100),
            'currency' => 643,
            'returnUrl' => url('/payment/'.$order->id.'/callback'),
            'failUrl' => url('/payment/'.$order->id.'/callback'),
            'description' => 'Заказ №'.$order->id,
            'jsonParams' => json_encode([
                'order_id' => (string) $order->id,
                'customer_id' => (string) $order->customer_id,
            ]),
        ];
    }

    protected function parseCreatePaymentResponse(array $response, Order $order): array
    {
        return [
            'payment_url' => $response['formUrl'] ?? $response['redirectUrl'] ?? null,
            'gateway_transaction_id' => $response['orderId'] ?? null,
            'payment_method_type' => 'bank_card',
        ];
    }

    protected function authenticateRequest($http): void
    {
        if (! empty($this->config['login']) && ! empty($this->config['password'])) {
            $http->withBasicAuth($this->config['login'], $this->config['password']);
        }
    }
}

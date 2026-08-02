<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;

class TinkoffGateway extends BaseAcquirerGateway implements PaymentGateway
{
    protected string $gatewayName = 'tinkoff';

    protected string $apiUrl = 'https://securepay.tinkoff.ru/v2';

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    protected function getCreatePaymentEndpoint(): string
    {
        return $this->apiUrl.'/Init';
    }

    protected function buildCreatePaymentPayload(Order $order): array
    {
        $payload = [
            'TerminalKey' => $this->config['terminal_key'] ?? '',
            'Amount' => (int) ($order->total_amount * 100),
            'Currency' => 643,
            'OrderId' => (string) $order->id,
            'Description' => 'Заказ №'.$order->id,
            'SuccessURL' => url('/payment/'.$order->id.'/callback'),
            'FailURL' => url('/payment/'.$order->id.'/callback'),
            'DATA' => [
                'order_id' => (string) $order->id,
                'customer_id' => (string) $order->customer_id,
            ],
        ];

        $payload['Token'] = $this->generateToken($payload);

        return $payload;
    }

    protected function parseCreatePaymentResponse(array $response, Order $order): array
    {
        return [
            'payment_url' => $response['PaymentURL'] ?? $response['paymentURL'] ?? null,
            'gateway_transaction_id' => $response['PaymentId'] ?? $response['paymentId'] ?? null,
            'payment_method_type' => 'bank_card',
        ];
    }

    protected function processWebhookEvent($transaction, string $event, array $object): \App\Models\Transaction
    {
        $status = $object['Status'] ?? '';

        return match ($status) {
            'CONFIRMED', 'AUTHORIZED' => $this->markAsCompleted($transaction),
            'REVERSED', 'REJECTED', 'REFUNDED' => $this->markAsFailed($transaction, $object['Message'] ?? 'Payment failed'),
            default => parent::processWebhookEvent($transaction, $event, $object),
        };
    }

    protected function authenticateRequest($http): void
    {
        if (! empty($this->config['terminal_password'])) {
            $http->withHeader('Authorization', 'Bearer '.$this->config['terminal_password']);
        }
    }

    private function generateToken(array $payload): string
    {
        $secretKey = $this->config['secret_key'] ?? '';

        $data = $payload;
        $data['Password'] = $secretKey;
        ksort($data);

        return hash('sha256', implode('', array_map(fn ($v) => (string) $v, $data)));
    }
}

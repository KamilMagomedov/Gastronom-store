<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Models\Transaction;

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
            'SuccessURL' => route('api.v1.payments.callback', ['order' => $order->id]),
            'FailURL' => route('api.v1.payments.callback', ['order' => $order->id]),
            'NotificationURL' => route('api.payments.webhook', [
                'gateway' => $this->getGatewayName(),
            ]),
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

    private function isValidNotificationToken(array $payload): bool
    {
        $receivedToken = $payload['Token'] ?? null;

        if (! is_string($receivedToken) || $receivedToken === '') {
            return false;
        }

        $expectedToken = $this->generateToken($payload);

        return hash_equals($expectedToken, $receivedToken);
    }

    public function handleWebhook(array $payload): Transaction
    {
        if (! $this->isValidNotificationToken($payload)) {
            throw new \RuntimeException('Invalid Tinkoff webhook token');
        }

        $orderId = $payload['OrderId'] ?? null;
        $paymentId = $payload['PaymentId'] ?? null;

        if (! $orderId || ! $paymentId) {
            throw new \RuntimeException('OrderId or PaymentId missing in Tinkoff webhook');
        }

        $order = Order::findOrFail($orderId);

        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('gateway_transaction_id', (string) $paymentId)
            ->first();

        if (! $transaction) {
            $transaction = $order->transactions()->create([
                'customer_id' => $order->customer_id,
                'amount' => isset($payload['Amount'])
                    ? ((int) $payload['Amount']) / 100
                    : $order->total_amount,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'gateway' => $this->getGatewayName(),
                'gateway_transaction_id' => (string) $paymentId,
                'status' => \App\Enums\TransactionStatus::PENDING,
                'gateway_response' => $payload,
            ]);
        } else {
            $transaction->update([
                'gateway_response' => $payload,
            ]);
        }

        return $this->processWebhookEvent(
            $transaction,
            '',
            $payload
        );
    }

    protected function processWebhookEvent($transaction, string $event, array $object): \App\Models\Transaction
    {
        $status = $object['Status'] ?? '';

        return match ($status) {
            'CONFIRMED' => $this->markAsCompleted($transaction),

            'REJECTED',
            'REVERSED',
            'CANCELED' => $this->markAsFailed(
                $transaction,
                $object['Message'] ?? 'Payment failed'
            ),

            default => $transaction,
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

        $data = array_filter(
            $payload,
            static fn ($value, $key) =>
                $key !== 'Token'
                && ! is_array($value)
                && ! is_object($value),
            ARRAY_FILTER_USE_BOTH
        );

        $data['Password'] = $secretKey;

        ksort($data);

        return hash(
            'sha256',
            implode('', array_map(
                static fn ($value) => (string) $value,
                $data
            ))
        );
    }
}

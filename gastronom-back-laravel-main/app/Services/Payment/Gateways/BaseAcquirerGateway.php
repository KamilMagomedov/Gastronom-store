<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseAcquirerGateway implements PaymentGateway
{
    protected array $config;

    protected string $gatewayName;

    protected string $apiUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    abstract protected function buildCreatePaymentPayload(Order $order): array;

    abstract protected function getCreatePaymentEndpoint(): string;

    abstract protected function parseCreatePaymentResponse(array $response, Order $order): array;

    public function isAvailable(): bool
    {
        return ! empty($this->config);
    }

    public function createPayment(Order $order): array
    {
        $idempotenceKey = (string) Str::uuid();
        $payload = $this->buildCreatePaymentPayload($order);
        $endpoint = $this->getCreatePaymentEndpoint();

        $response = $this->sendRequest('POST', $endpoint, $payload, $idempotenceKey);

        if (! $response['success']) {
            Log::error("{$this->getGatewayName()} createPayment failed", [
                'order_id' => $order->id,
                'status' => $response['status'] ?? null,
                'body' => $response['body'] ?? null,
            ]);

            throw new \RuntimeException(
                'Payment creation failed: '.($response['body']['description'] ?? 'Unknown error')
            );
        }

        $data = $response['body'];
        $result = $this->parseCreatePaymentResponse($data, $order);

        $transaction = $order->transactions()->create([
            'customer_id' => $order->customer_id,
            'amount' => $order->total_amount,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'gateway' => $this->getGatewayName(),
            'gateway_transaction_id' => $result['gateway_transaction_id'] ?? $data['id'] ?? null,
            'status' => \App\Enums\TransactionStatus::PENDING,
            'gateway_response' => $data,
        ]);

        return [
            'payment_url' => $result['payment_url'] ?? null,
            'transaction_id' => $transaction->id,
            'gateway_transaction_id' => $data['id'] ?? null,
            'payment_method_type' => $result['payment_method_type'] ?? 'bank_card',
        ];
    }

    public function handleWebhook(array $payload): Transaction
    {
        $event = $payload['event'] ?? '';
        $object = $payload['object'] ?? $payload;

        $gatewayTransactionId = $object['id'] ?? '';
        $metadata = $object['metadata'] ?? [];
        $orderId = $metadata['order_id'] ?? null;

        if (! $orderId) {
            throw new \RuntimeException('Order ID not found in webhook metadata');
        }

        $transaction = Transaction::query()
            ->where('gateway_transaction_id', $gatewayTransactionId)
            ->where('order_id', $orderId)
            ->first();

        if (! $transaction) {
            $order = \App\Models\Order::findOrFail($orderId);

            $transaction = $order->transactions()->create([
                'customer_id' => $order->customer_id,
                'amount' => $object['amount']['value'] ?? $order->total_amount,
                'currency' => $object['amount']['currency'] ?? 'RUB',
                'payment_method' => 'card',
                'gateway' => $this->getGatewayName(),
                'gateway_transaction_id' => $gatewayTransactionId,
                'status' => \App\Enums\TransactionStatus::PENDING,
                'gateway_response' => $payload,
            ]);
        }

        $transaction->update(['gateway_response' => $payload]);

        return $this->processWebhookEvent($transaction, $event, $object);
    }

    protected function processWebhookEvent(Transaction $transaction, string $event, array $object): Transaction
    {
        return match ($event) {
            'payment.succeeded' => $this->markAsCompleted($transaction),
            'payment.canceled' => $this->markAsFailed($transaction, $object['cancellation_details']['reason'] ?? 'Payment cancelled'),
            default => $transaction,
        };
    }

    protected function markAsCompleted(Transaction $transaction): Transaction
    {
        $transaction->update([
            'status' => \App\Enums\TransactionStatus::COMPLETED,
            'processed_at' => now(),
        ]);

        $transaction->order->update([
            'payment_status' => \App\Enums\PaymentStatus::PAID->value,
        ]);

        return $transaction;
    }

    protected function markAsFailed(Transaction $transaction, string $reason = 'Payment failed'): Transaction
    {
        $transaction->update([
            'status' => \App\Enums\TransactionStatus::FAILED,
            'processed_at' => now(),
            'notes' => $reason,
        ]);

        $transaction->order->update([
            'payment_status' => \App\Enums\PaymentStatus::FAILED->value,
        ]);

        return $transaction;
    }

    protected function sendRequest(string $method, string $url, array $data = [], ?string $idempotenceKey = null): array
    {
        $http = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
        ]);

        if ($idempotenceKey) {
            $http = $http->withHeader('Idempotence-Key', $idempotenceKey);
        }

        $this->authenticateRequest($http);

        $response = $http->$method($url, $data);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    protected function authenticateRequest($http): void
    {
        // Override in specific gateway if needed (e.g. basic auth)
    }
}

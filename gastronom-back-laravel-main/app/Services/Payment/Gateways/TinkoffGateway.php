<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TinkoffGateway extends BaseAcquirerGateway implements PaymentGateway
{
    protected string $gatewayName = 'tinkoff';

    protected string $apiUrl = 'https://securepay.tinkoff.ru/v2';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! empty($config['api_url'])) {
            $this->apiUrl = rtrim($config['api_url'], '/');
        }
    }

    public function isAvailable(): bool
    {
        return ! empty($this->config['terminal_key'])
            && ! empty($this->config['secret_key']);
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function createPayment(Order $order): array
    {
        $reservation = DB::transaction(function () use ($order): array {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedOrder->getRawOriginal('payment_status')
                === PaymentStatus::PAID->value
            ) {
                throw new PaymentException('Order is already paid');
            }

            $existingTransaction = Transaction::query()
                ->where('order_id', $lockedOrder->id)
                ->where('gateway', $this->getGatewayName())
                ->whereIn('status', [
                    TransactionStatus::PENDING->value,
                    TransactionStatus::PROCESSING->value,
                ])
                ->latest('id')
                ->first();

            if ($existingTransaction) {
                return [
                    'transaction' => $existingTransaction,
                    'created' => false,
                ];
            }

            $transaction = Transaction::query()->create([
                'order_id' => $lockedOrder->id,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => $lockedOrder->total_amount,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'gateway' => $this->getGatewayName(),
                'gateway_order_id' => (string) Str::uuid(),
                'gateway_transaction_id' => null,
                'status' => TransactionStatus::PROCESSING,
                'gateway_response' => null,
            ]);

            return [
                'transaction' => $transaction,
                'created' => true,
            ];
        });

        /** @var Transaction $transaction */
        $transaction = $reservation['transaction'];

        if (! $reservation['created']) {
            if ($transaction->status === TransactionStatus::PENDING) {
                $gatewayResponse = $transaction->gateway_response ?? [];

                $paymentUrl = $gatewayResponse['PaymentURL']
                    ?? $gatewayResponse['paymentURL']
                    ?? null;

                if (
                    $paymentUrl
                    && $transaction->gateway_transaction_id
                ) {
                    return [
                        'payment_url' => $paymentUrl,
                        'transaction_id' => $transaction->id,
                        'gateway_transaction_id' => $transaction->gateway_transaction_id,
                        'payment_method_type' => 'bank_card',
                    ];
                }
            }

            throw new PaymentException(
                'Payment initialization is already in progress'
            );
        }

        $payload = $this->buildCreatePaymentPayload(
            $order,
            (string) $transaction->gateway_order_id
        );

        $response = $this->sendRequest(
            'POST',
            $this->getCreatePaymentEndpoint(),
            $payload,
            (string) $transaction->gateway_order_id
        );

        $data = $response['body'];

        if (
            ! $response['success']
            || (($data['Success'] ?? true) !== true)
        ) {
            Log::error('tinkoff createPayment failed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'gateway_order_id' => $transaction->gateway_order_id,
                'status' => $response['status'] ?? null,
                'body' => $data,
            ]);

            throw new \RuntimeException('Payment creation failed');
        }

        $result = $this->parseCreatePaymentResponse($data, $order);

        $gatewayTransactionId = $result['gateway_transaction_id']
            ?? $data['PaymentId']
            ?? $data['paymentId']
            ?? null;

        if (
            $gatewayTransactionId === null
            || (string) $gatewayTransactionId === ''
        ) {
            throw new \RuntimeException(
                'Payment provider response missing transaction id'
            );
        }

        $gatewayTransactionId = (string) $gatewayTransactionId;

        try {
            $transaction = DB::transaction(
                function () use (
                    $transaction,
                    $gatewayTransactionId,
                    $data
                ): Transaction {
                    $currentTransaction = Transaction::query()
                        ->whereKey($transaction->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $currentTransaction->gateway_transaction_id !== null
                        && (string) $currentTransaction->gateway_transaction_id
                            !== $gatewayTransactionId
                    ) {
                        throw new \RuntimeException(
                            'Tinkoff payment id mismatch'
                        );
                    }

                    if (
                        $currentTransaction->status
                        === TransactionStatus::PROCESSING
                    ) {
                        $currentTransaction->update([
                            'gateway_transaction_id' => $gatewayTransactionId,
                            'status' => TransactionStatus::PENDING,
                            'gateway_response' => $data,
                        ]);
                    }

                    return $currentTransaction->refresh();
                }
            );
        } catch (UniqueConstraintViolationException $exception) {
            throw new \RuntimeException(
                'Payment transaction does not belong to this order',
                0,
                $exception
            );
        }

        return [
            'payment_url' => $result['payment_url'] ?? null,
            'transaction_id' => $transaction->id,
            'gateway_transaction_id' => $gatewayTransactionId,
            'payment_method_type' => $result['payment_method_type']
                ?? 'bank_card',
        ];
    }

    protected function getCreatePaymentEndpoint(): string
    {
        return $this->apiUrl.'/Init';
    }

    protected function buildCreatePaymentPayload(
        Order $order,
        ?string $gatewayOrderId = null
    ): array
    {
        $payload = [
            'TerminalKey' => $this->config['terminal_key'] ?? '',
            'Amount' => (int) ($order->total_amount * 100),
            'Currency' => 643,
            'OrderId' => $gatewayOrderId ?? (string) $order->id,
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

        $terminalKey = $payload['TerminalKey'] ?? null;
        $expectedTerminalKey = $this->config['terminal_key'] ?? null;

        if (
            ! is_string($terminalKey)
            || ! is_string($expectedTerminalKey)
            || $expectedTerminalKey === ''
            || $terminalKey !== $expectedTerminalKey
        ) {
            throw new \RuntimeException('Invalid Tinkoff terminal key');
        }

        $orderId = $payload['OrderId'] ?? null;
        $paymentId = $payload['PaymentId'] ?? null;

        if (! $orderId || ! $paymentId) {
            throw new \RuntimeException(
                'OrderId or PaymentId missing in Tinkoff webhook'
            );
        }

        $transaction = Transaction::query()
            ->where('gateway', $this->getGatewayName())
            ->where('gateway_order_id', (string) $orderId)
            ->first();

        if ($transaction) {
            $order = $transaction->order;

            if (
                $transaction->gateway_transaction_id !== null
                && (string) $transaction->gateway_transaction_id !== (string) $paymentId
            ) {
                throw new \RuntimeException(
                    'Tinkoff webhook payment id mismatch'
                );
            }
        } else {
            // Backward compatibility for payments created before gateway_order_id existed.
            if (! ctype_digit((string) $orderId)) {
                throw new \RuntimeException(
                    'Tinkoff payment attempt not found'
                );
            }

            $order = Order::findOrFail((int) $orderId);

            $transaction = Transaction::query()
                ->where('order_id', $order->id)
                ->where('gateway_transaction_id', (string) $paymentId)
                ->first();
        }

        $amount = $payload['Amount'] ?? null;

        if (! is_numeric($amount)) {
            throw new \RuntimeException(
                'Amount missing in Tinkoff webhook'
            );
        }

        $expectedAmount = (int) round(
            ((float) $order->total_amount) * 100
        );

        if ((int) $amount !== $expectedAmount) {
            throw new \RuntimeException(
                'Tinkoff webhook amount mismatch'
            );
        }

        $status = $payload['Status'] ?? '';

        if (
            $transaction
            && $transaction->isCompleted()
            && in_array(
                $status,
                ['CONFIRMED', 'REJECTED', 'REVERSED', 'CANCELED'],
                true
            )
        ) {
            return $transaction;
        }

        if (! $transaction) {
            $transaction = Transaction::query()->createOrFirst(
                [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'gateway' => $this->getGatewayName(),
                    'gateway_transaction_id' => (string) $paymentId,
                ],
                [
                    'amount' => ((int) $amount) / 100,
                    'currency' => 'RUB',
                    'payment_method' => 'card',
                    'status' => \App\Enums\TransactionStatus::PENDING,
                    'gateway_response' => $payload,
                ]
            );
        } else {
            $transaction->update([
                'gateway_transaction_id' => (string) $paymentId,
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
        if (! empty($this->config['api_token'])) {
            $http->withToken($this->config['api_token']);
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

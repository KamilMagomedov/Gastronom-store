<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\Gateways\TinkoffGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class TinkoffGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_payment_reuses_transaction_created_by_concurrent_webhook(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $paymentId = 'init-race-'.$order->id;
        $paymentUrl = 'https://example.test/payment/'.$paymentId;

        $webhookPayload = [
            'TerminalKey' => $config['terminal_key'],
            'OrderId' => (string) $order->id,
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => $paymentId,
            'ErrorCode' => '0',
            'Amount' => (int) round(
                ((float) $order->total_amount) * 100
            ),
        ];

        $tokenData = $webhookPayload;
        $tokenData['Password'] = $config['secret_key'];

        ksort($tokenData);

        $webhookPayload['Token'] = hash(
            'sha256',
            implode('', array_map(
                static fn ($value) => (string) $value,
                $tokenData
            ))
        );

        Http::fake([
            'https://securepay.tinkoff.ru/v2/Init' => function ($request) use (
                $gateway,
                $webhookPayload,
                $config,
                $paymentId,
                $paymentUrl
            ) {
                $webhookPayload['OrderId'] = (string) $request['OrderId'];

                $tokenData = $webhookPayload;
                unset($tokenData['Token']);

                $tokenData['Password'] = $config['secret_key'];
                ksort($tokenData);

                $webhookPayload['Token'] = hash(
                    'sha256',
                    implode('', array_map(
                        static fn ($value) => (string) $value,
                        $tokenData
                    ))
                );

                $gateway->handleWebhook($webhookPayload);

                return Http::response([
                    'Success' => true,
                    'PaymentId' => $paymentId,
                    'PaymentURL' => $paymentUrl,
                ], 200);
            },
        ]);

        $result = $gateway->createPayment($order);

        $transaction = Transaction::where(
            'gateway_transaction_id',
            $paymentId
        )->firstOrFail();

        $order->refresh();

        $this->assertSame(
            1,
            Transaction::where(
                'gateway_transaction_id',
                $paymentId
            )->count()
        );

        $this->assertSame(
            TransactionStatus::COMPLETED,
            $transaction->status
        );

        $this->assertSame('paid', $order->payment_status);

        $this->assertSame(
            $transaction->id,
            $result['transaction_id']
        );

        $this->assertSame(
            $paymentId,
            $result['gateway_transaction_id']
        );

        $this->assertSame(
            $paymentUrl,
            $result['payment_url']
        );

        Http::assertSentCount(1);
    }

    public function test_create_payment_rejects_transaction_belonging_to_another_order(): void
    {
        $firstCustomer = Customer::factory()->create();
        $secondCustomer = Customer::factory()->create();

        $existingOrder = Order::factory()->create([
            'customer_id' => $firstCustomer->id,
            'payment_status' => 'pending',
        ]);

        $targetOrder = Order::factory()->create([
            'customer_id' => $secondCustomer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $paymentId = 'foreign-payment-id';

        $existingTransaction = $existingOrder->transactions()->create([
            'customer_id' => $firstCustomer->id,
            'amount' => $existingOrder->total_amount,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'gateway' => 'tinkoff',
            'gateway_transaction_id' => $paymentId,
            'status' => TransactionStatus::PENDING,
            'gateway_response' => [],
        ]);

        Http::fake([
            'https://securepay.tinkoff.ru/v2/Init' => Http::response([
                'Success' => true,
                'PaymentId' => $paymentId,
                'PaymentURL' => 'https://example.test/payment/'.$paymentId,
            ], 200),
        ]);

        try {
            $gateway->createPayment($targetOrder);

            $this->fail(
                'Expected createPayment to reject a PaymentId belonging to another order.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Payment transaction does not belong to this order',
                $exception->getMessage()
            );
        }

        $existingTransaction->refresh();

        $this->assertSame(
            $existingOrder->id,
            $existingTransaction->order_id
        );

        $this->assertSame(
            $firstCustomer->id,
            $existingTransaction->customer_id
        );

        $this->assertSame(
            1,
            Transaction::where(
                'gateway_transaction_id',
                $paymentId
            )->count()
        );

        $this->assertSame(
            0,
            Transaction::where('order_id', $targetOrder->id)
                ->where('gateway_transaction_id', $paymentId)
                ->count()
        );
    }

    public function test_create_payment_does_not_start_second_payment_for_same_active_order(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $firstPaymentId = 'first-payment-'.$order->id;
        $secondPaymentId = 'second-payment-'.$order->id;

        Http::fake([
            'https://securepay.tinkoff.ru/v2/Init' => Http::sequence()
                ->push([
                    'Success' => true,
                    'PaymentId' => $firstPaymentId,
                    'PaymentURL' => 'https://example.test/payment/'.$firstPaymentId,
                ], 200)
                ->push([
                    'Success' => true,
                    'PaymentId' => $secondPaymentId,
                    'PaymentURL' => 'https://example.test/payment/'.$secondPaymentId,
                ], 200),
        ]);

        $firstResult = $gateway->createPayment($order);
        $secondResult = $gateway->createPayment($order);

        Http::assertSentCount(1);

        $this->assertSame(
            $firstResult['transaction_id'],
            $secondResult['transaction_id']
        );

        $this->assertSame(
            $firstResult['gateway_transaction_id'],
            $secondResult['gateway_transaction_id']
        );

        $this->assertSame(
            $firstResult['payment_url'],
            $secondResult['payment_url']
        );

        $this->assertSame(
            1,
            Transaction::where('order_id', $order->id)
                ->where('gateway', 'tinkoff')
                ->whereIn('status', [
                    TransactionStatus::PENDING->value,
                    TransactionStatus::PROCESSING->value,
                ])
                ->count()
        );
    }

    public function test_create_payment_timeout_preserves_attempt_and_prevents_duplicate_init(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $gateway = new TinkoffGateway([
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ]);

        $requestCount = 0;

        Http::fake([
            'https://securepay.tinkoff.ru/v2/Init' => function () use (&$requestCount) {
                $requestCount++;

                throw new ConnectionException(
                    'Simulated payment provider timeout'
                );
            },
        ]);

        try {
            $gateway->createPayment($order);

            $this->fail('Expected payment timeout exception.');
        } catch (ConnectionException $exception) {
            $this->assertSame(
                'Simulated payment provider timeout',
                $exception->getMessage()
            );
        }

        $transaction = Transaction::query()
            ->where('order_id', $order->id)
            ->where('gateway', 'tinkoff')
            ->sole();

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->status
        );

        $this->assertNotEmpty(
            $transaction->gateway_order_id
        );

        $this->assertNull(
            $transaction->gateway_transaction_id
        );

        try {
            $gateway->createPayment($order);

            $this->fail(
                'Expected duplicate initialization to be rejected.'
            );
        } catch (\App\Services\Payment\Exceptions\PaymentException $exception) {
            $this->assertSame(
                'Payment initialization is already in progress',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            1,
            $requestCount,
            'Payment initialization must not send a second /Init request after timeout.'
        );

        $this->assertSame(
            1,
            Transaction::query()
                ->where('order_id', $order->id)
                ->where('gateway', 'tinkoff')
                ->count()
        );
    }

    public function test_concurrent_payment_initialization_does_not_send_second_init_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $requestCount = 0;
        $secondAttemptException = null;

        $paymentId = 'concurrent-payment-'.$order->id;
        $paymentUrl = 'https://example.test/payment/'.$paymentId;

        Http::fake([
            'https://securepay.tinkoff.ru/v2/Init' => function () use (
                &$requestCount,
                &$secondAttemptException,
                $gateway,
                $order,
                $paymentId,
                $paymentUrl
            ) {
                $requestCount++;

                if ($requestCount === 1) {
                    try {
                        $gateway->createPayment($order);
                    } catch (\Throwable $exception) {
                        $secondAttemptException = $exception;
                    }
                }

                return Http::response([
                    'Success' => true,
                    'PaymentId' => $paymentId.'-'.$requestCount,
                    'PaymentURL' => $paymentUrl.'-'.$requestCount,
                ], 200);
            },
        ]);

        $gateway->createPayment($order);

        $this->assertSame(
            1,
            $requestCount,
            'Concurrent payment initialization sent more than one /Init request.'
        );

        $this->assertNotNull(
            $secondAttemptException,
            'Concurrent payment initialization should not start another provider request.'
        );
    }

    public function test_transaction_can_store_unique_gateway_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $gatewayOrderId = 'attempt-'.$order->id.'-test';

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => $gatewayOrderId,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $this->assertSame(
            $gatewayOrderId,
            $transaction->gateway_order_id
        );

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => $gatewayOrderId,
            'status' => TransactionStatus::PROCESSING,
        ]);
    }

    public function test_webhook_resolves_processing_transaction_by_gateway_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $gatewayOrderId = 'attempt-'.$order->id.'-webhook';
        $paymentId = 'webhook-payment-'.$order->id;

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => $order->total_amount,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'gateway' => 'tinkoff',
            'gateway_order_id' => $gatewayOrderId,
            'gateway_transaction_id' => null,
            'status' => TransactionStatus::PROCESSING,
            'gateway_response' => null,
            'processed_at' => null,
        ]);

        $payload = [
            'TerminalKey' => $config['terminal_key'],
            'OrderId' => $gatewayOrderId,
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => $paymentId,
            'ErrorCode' => '0',
            'Amount' => (int) round(
                ((float) $order->total_amount) * 100
            ),
        ];

        $tokenData = $payload;
        $tokenData['Password'] = $config['secret_key'];
        ksort($tokenData);

        $payload['Token'] = hash(
            'sha256',
            implode('', array_map(
                static fn ($value) => (string) $value,
                $tokenData
            ))
        );

        $result = $gateway->handleWebhook($payload);

        $transaction->refresh();
        $order->refresh();

        $this->assertSame(
            $transaction->id,
            $result->id
        );

        $this->assertSame(
            $paymentId,
            $transaction->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::COMPLETED,
            $transaction->status
        );

        $this->assertSame(
            'paid',
            $order->payment_status
        );

        $this->assertSame(
            1,
            Transaction::where('order_id', $order->id)->count()
        );
    }

    public function test_webhook_rejects_different_payment_id_for_existing_gateway_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gateway = new TinkoffGateway($config);

        $gatewayOrderId = 'attempt-'.$order->id.'-mismatch';
        $existingPaymentId = 'existing-payment-'.$order->id;
        $differentPaymentId = 'different-payment-'.$order->id;

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => $order->total_amount,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'gateway' => 'tinkoff',
            'gateway_order_id' => $gatewayOrderId,
            'gateway_transaction_id' => $existingPaymentId,
            'status' => TransactionStatus::PROCESSING,
            'gateway_response' => null,
            'processed_at' => null,
        ]);

        $payload = [
            'TerminalKey' => $config['terminal_key'],
            'OrderId' => $gatewayOrderId,
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => $differentPaymentId,
            'ErrorCode' => '0',
            'Amount' => (int) round(
                ((float) $order->total_amount) * 100
            ),
        ];

        $tokenData = $payload;
        $tokenData['Password'] = $config['secret_key'];
        ksort($tokenData);

        $payload['Token'] = hash(
            'sha256',
            implode('', array_map(
                static fn ($value) => (string) $value,
                $tokenData
            ))
        );

        try {
            $gateway->handleWebhook($payload);

            $this->fail(
                'Expected webhook with mismatched PaymentId to be rejected.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Tinkoff webhook payment id mismatch',
                $exception->getMessage()
            );
        }

        $transaction->refresh();
        $order->refresh();

        $this->assertSame(
            $existingPaymentId,
            $transaction->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->status
        );

        $this->assertSame(
            'pending',
            $order->payment_status
        );

        $this->assertSame(
            1,
            Transaction::where('gateway_order_id', $gatewayOrderId)->count()
        );
    }

    public function test_check_order_status_sends_signed_request_without_modifying_transaction(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $config = [
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ];

        $gatewayOrderId = 'attempt-'.$order->id.'-check';

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => $gatewayOrderId,
            'gateway_transaction_id' => null,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = new TinkoffGateway($config);

        $bankResponse = [
            'Success' => true,
            'OrderId' => $gatewayOrderId,
            'Payments' => [],
        ];

        Http::fake([
            'https://securepay.tinkoff.ru/v2/CheckOrder'
                => Http::response($bankResponse, 200),
        ]);

        $result = $gateway->checkOrderStatus($gatewayOrderId);

        $this->assertSame($bankResponse, $result);

        $tokenData = [
            'TerminalKey' => $config['terminal_key'],
            'OrderId' => $gatewayOrderId,
            'Password' => $config['secret_key'],
        ];

        ksort($tokenData);

        $expectedToken = hash(
            'sha256',
            implode('', array_map(
                static fn ($value) => (string) $value,
                $tokenData
            ))
        );

        Http::assertSent(function ($request) use (
            $config,
            $gatewayOrderId,
            $expectedToken
        ) {
            return $request->method() === 'POST'
                && $request->url()
                    === 'https://securepay.tinkoff.ru/v2/CheckOrder'
                && $request['TerminalKey'] === $config['terminal_key']
                && $request['OrderId'] === $gatewayOrderId
                && $request['Token'] === $expectedToken;
        });

        Http::assertSentCount(1);

        $transaction->refresh();

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->status
        );

        $this->assertNull(
            $transaction->gateway_transaction_id
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_check_order_status_rejects_provider_error(): void
    {
        $gateway = new TinkoffGateway([
            'terminal_key' => 'tink_test_terminal',
            'secret_key' => 'tink_test_secret',
        ]);

        Http::fake([
            'https://securepay.tinkoff.ru/v2/CheckOrder' =>
                Http::response([
                    'Success' => false,
                    'ErrorCode' => '5',
                    'Message' => 'Order not found',
                ], 200),
        ]);

        $this->expectException(
            \App\Services\Payment\Exceptions\PaymentException::class
        );

        $this->expectExceptionMessage(
            'Unable to retrieve payment status from provider'
        );

        $gateway->checkOrderStatus('unknown-order-id');
    }

    public function test_check_order_status_requires_gateway_configuration(): void
    {
        $gateway = new TinkoffGateway([]);

        Http::preventStrayRequests();

        $this->expectException(
            \App\Services\Payment\Exceptions\PaymentException::class
        );

        $this->expectExceptionMessage(
            'Tinkoff payment gateway is not configured'
        );

        $gateway->checkOrderStatus('test-order-id');
    }
}
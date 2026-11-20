<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\TinkoffReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TinkoffReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspect_returns_bank_response_without_modifying_payment(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-123',
            'gateway_transaction_id' => null,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $bankResponse = [
            'Success' => true,
            'OrderId' => 'attempt-123',
            'Payments' => [],
        ];

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-123')
            ->andReturn($bankResponse);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame($bankResponse, $result);

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertNull(
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_completed_transaction(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-completed',
            'status' => TransactionStatus::COMPLETED,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);
        $gateway->shouldNotReceive('checkOrderStatus');

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction is not eligible for reconciliation'
        );

        $service->inspect($transaction, $gateway);
    }

    public function test_inspect_rejects_transaction_without_gateway_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => null,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);
        $gateway->shouldNotReceive('checkOrderStatus');

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Gateway order ID is missing'
        );

        $service->inspect($transaction, $gateway);
    }

    public function test_inspect_uses_fresh_database_status(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-stale',
            'status' => TransactionStatus::PROCESSING,
        ]);

        // Database changes, but $transaction remains stale.
        Transaction::whereKey($transaction->id)->update([
            'status' => TransactionStatus::COMPLETED->value,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);
        $gateway->shouldNotReceive('checkOrderStatus');

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction is not eligible for reconciliation'
        );

        $service->inspect($transaction, $gateway);
    }

    public function test_inspect_rejects_transaction_from_another_gateway(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'sberbank',
            'gateway_order_id' => 'other-bank-attempt',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);
        $gateway->shouldNotReceive('checkOrderStatus');

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction is not eligible for reconciliation'
        );

        $service->inspect($transaction, $gateway);
    }

    public function test_inspect_uses_fresh_gateway_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'old-attempt-id',
            'gateway_transaction_id' => null,
            'status' => TransactionStatus::PROCESSING,
        ]);

        Transaction::query()
            ->whereKey($transaction->id)
            ->update([
                'gateway_order_id' => 'new-attempt-id',
            ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('new-attempt-id')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'new-attempt-id',
                'Payments' => [],
            ]);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame(
            'new-attempt-id',
            $result['OrderId']
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_mismatched_bank_order_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-123',
            'gateway_transaction_id' => null,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-123')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-999',
                'Payments' => [],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected bank OrderId mismatch.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Bank order ID mismatch',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_mismatched_bank_payment_amount(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'total_amount' => 1000.00,
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-amount-check',
            'gateway_transaction_id' => null,
            'amount' => 1000.00,
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-amount-check')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-amount-check',
                'Payments' => [
                    [
                        'PaymentId' => 123456,
                        'Amount' => 50000,
                        'Status' => 'CONFIRMED',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected bank payment amount mismatch.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Bank payment amount mismatch',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_mismatched_bank_payment_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-payment-id-check',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-payment-id-check')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-payment-id-check',
                'Payments' => [
                    [
                        'PaymentId' => 999999,
                        'Amount' => 100000,
                        'Status' => 'CONFIRMED',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected bank PaymentId mismatch.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Bank payment ID mismatch',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            '123456',
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_accepts_matching_bank_payment_id(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-matching-id',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $bankResponse = [
            'Success' => true,
            'OrderId' => 'attempt-matching-id',
            'Payments' => [
                [
                    'PaymentId' => 123456,
                    'Amount' => 100000,
                    'Status' => 'CONFIRMED',
                    'Success' => true,
                ],
            ],
        ];

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-matching-id')
            ->andReturn($bankResponse);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame($bankResponse, $result);

        $this->assertSame(
            '123456',
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_accepts_bank_payment_when_local_payment_id_is_missing(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-timeout',
            'gateway_transaction_id' => null,
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $bankResponse = [
            'Success' => true,
            'OrderId' => 'attempt-timeout',
            'Payments' => [
                [
                    'PaymentId' => 123456,
                    'Amount' => 100000,
                    'Status' => 'CONFIRMED',
                    'Success' => true,
                ],
            ],
        ];

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-timeout')
            ->andReturn($bankResponse);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame($bankResponse, $result);

        // Inspection must not automatically save PaymentId.
        $this->assertNull(
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_ambiguous_bank_payments_when_local_payment_id_is_missing(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-ambiguous',
            'gateway_transaction_id' => null,
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-ambiguous')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-ambiguous',
                'Payments' => [
                    [
                        'PaymentId' => 111111,
                        'Amount' => 100000,
                        'Status' => 'REJECTED',
                        'Success' => false,
                    ],
                    [
                        'PaymentId' => 222222,
                        'Amount' => 100000,
                        'Status' => 'CONFIRMED',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected ambiguous bank payments.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Ambiguous bank payments',
                $exception->getMessage()
            );
        }

        $this->assertNull(
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_accepts_matching_payment_among_multiple_bank_payments(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-multiple-payments',
            'gateway_transaction_id' => '222222',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $bankResponse = [
            'Success' => true,
            'OrderId' => 'attempt-multiple-payments',
            'Payments' => [
                [
                    'PaymentId' => 111111,
                    'Amount' => 50000,
                    'Status' => 'REJECTED',
                    'Success' => false,
                ],
                [
                    'PaymentId' => 222222,
                    'Amount' => 100000,
                    'Status' => 'CONFIRMED',
                    'Success' => true,
                ],
            ],
        ];

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-multiple-payments')
            ->andReturn($bankResponse);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame($bankResponse, $result);

        $this->assertSame(
            '222222',
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_duplicate_matching_payment_ids(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-duplicate-id',
            'gateway_transaction_id' => '222222',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-duplicate-id')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-duplicate-id',
                'Payments' => [
                    [
                        'PaymentId' => 222222,
                        'Amount' => 100000,
                        'Status' => 'CONFIRMED',
                        'Success' => true,
                    ],
                    [
                        'PaymentId' => 222222,
                        'Amount' => 100000,
                        'Status' => 'REJECTED',
                        'Success' => false,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected duplicate matching PaymentId rejection.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Bank payment ID mismatch',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_missing_payments_field(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-missing-payments',
            'gateway_transaction_id' => null,
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-missing-payments')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-missing-payments',
                // Payments is deliberately missing.
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected invalid bank payments response.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Invalid bank payments response',
                $exception->getMessage()
            );
        }

        $this->assertNull(
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_payment_without_status(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-missing-status',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-missing-status')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-missing-status',
                'Payments' => [
                    [
                        'PaymentId' => 123456,
                        'Amount' => 100000,
                        // Status is deliberately missing.
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected invalid bank payment status.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Invalid bank payment status',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_unknown_bank_payment_status(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-unknown-status',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-unknown-status')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-unknown-status',
                'Payments' => [
                    [
                        'PaymentId' => 123456,
                        'Amount' => 100000,
                        'Status' => 'UNKNOWN_FAKE_STATUS',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected unknown bank payment status.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Unknown bank payment status',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_rejects_bank_response_without_success(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-missing-success',
            'gateway_transaction_id' => null,
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-missing-success')
            ->andReturn([
                // Success is deliberately missing.
                'OrderId' => 'attempt-missing-success',
                'Payments' => [],
            ]);

        $service = new TinkoffReconciliationService();

        try {
            $service->inspect($transaction, $gateway);

            $this->fail('Expected invalid bank response.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Invalid bank response',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_accepts_authorized_payment_without_marking_order_paid(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'total_amount' => '1000.00',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-authorized',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $bankResponse = [
            'Success' => true,
            'OrderId' => 'attempt-authorized',
            'Payments' => [
                [
                    'PaymentId' => 123456,
                    'Amount' => 100000,
                    'Status' => 'AUTHORIZED',
                    'Success' => true,
                ],
            ],
        ];

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-authorized')
            ->andReturn($bankResponse);

        $service = new TinkoffReconciliationService();

        $result = $service->inspect($transaction, $gateway);

        $this->assertSame($bankResponse, $result);

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );

        $this->assertSame(
            '123456',
            $transaction->fresh()->gateway_transaction_id
        );
    }

    public function test_inspect_summary_classifies_authorized_payment_without_database_changes(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
            'total_amount' => '1000.00',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-summary',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-summary')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-summary',
                'Payments' => [
                    [
                        'PaymentId' => 123456,
                        'Amount' => 100000,
                        'Status' => 'AUTHORIZED',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        $result = $service->inspectSummary(
            $transaction,
            $gateway
        );

        $this->assertSame([
            'payment_id' => '123456',
            'bank_status' => 'AUTHORIZED',
            'classification' => 'pending',
        ], $result);

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );

        $this->assertSame(
            '123456',
            $transaction->fresh()->gateway_transaction_id
        );
    }

    public function test_inspect_summary_returns_unknown_when_payments_are_empty(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-empty-payments',
            'gateway_transaction_id' => null,
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-empty-payments')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-empty-payments',
                'Payments' => [],
            ]);

        $service = new TinkoffReconciliationService();

        $result = $service->inspectSummary(
            $transaction,
            $gateway
        );

        $this->assertSame([
            'payment_id' => null,
            'bank_status' => null,
            'classification' => 'unknown',
        ], $result);

        $this->assertNull(
            $transaction->fresh()->gateway_transaction_id
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );

        $this->assertSame(
            'pending',
            $order->fresh()->payment_status
        );
    }

    public function test_inspect_summary_rejects_transaction_completed_during_bank_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-concurrent-webhook',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-concurrent-webhook')
            ->andReturnUsing(function () use ($transaction) {
                // Simulate a webhook completing the transaction
                // while the bank request is in progress.
                Transaction::query()
                    ->whereKey($transaction->id)
                    ->update([
                        'status' => TransactionStatus::COMPLETED->value,
                    ]);

                return [
                    'Success' => true,
                    'OrderId' => 'attempt-concurrent-webhook',
                    'Payments' => [
                        [
                            'PaymentId' => 123456,
                            'Amount' => 100000,
                            'Status' => 'AUTHORIZED',
                            'Success' => true,
                        ],
                    ],
                ];
            });

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction changed during reconciliation'
        );

        $service->inspectSummary($transaction, $gateway);
    }

    public function test_inspect_summary_rejects_payment_id_changed_during_bank_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-payment-id-change',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-payment-id-change')
            ->andReturnUsing(function () use ($transaction) {
                Transaction::query()
                    ->whereKey($transaction->id)
                    ->update([
                        'gateway_transaction_id' => '999999',
                    ]);

                return [
                    'Success' => true,
                    'OrderId' => 'attempt-payment-id-change',
                    'Payments' => [
                        [
                            'PaymentId' => 123456,
                            'Amount' => 100000,
                            'Status' => 'AUTHORIZED',
                            'Success' => true,
                        ],
                    ],
                ];
            });

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);

        $service->inspectSummary($transaction, $gateway);
    }

    public function test_inspect_summary_rejects_transaction_deleted_during_bank_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-deleted',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-deleted')
            ->andReturnUsing(function () use ($transaction) {
                // Simulate deletion during the bank request.
                Transaction::query()
                    ->whereKey($transaction->id)
                    ->delete();

                return [
                    'Success' => true,
                    'OrderId' => 'attempt-deleted',
                    'Payments' => [
                        [
                            'PaymentId' => 123456,
                            'Amount' => 100000,
                            'Status' => 'AUTHORIZED',
                            'Success' => true,
                        ],
                    ],
                ];
            });

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction changed during reconciliation'
        );

        $service->inspectSummary($transaction, $gateway);
    }

    public function test_inspect_summary_uses_fresh_payment_id_instead_of_stale_model(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-stale-model',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        // Update the database without refreshing the Eloquent model.
        Transaction::query()
            ->whereKey($transaction->id)
            ->update([
                'gateway_transaction_id' => '999999',
            ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-stale-model')
            ->andReturn([
                'Success' => true,
                'OrderId' => 'attempt-stale-model',
                'Payments' => [
                    [
                        'PaymentId' => 999999,
                        'Amount' => 100000,
                        'Status' => 'AUTHORIZED',
                        'Success' => true,
                    ],
                ],
            ]);

        $service = new TinkoffReconciliationService();

        $result = $service->inspectSummary(
            $transaction,
            $gateway
        );

        $this->assertSame('999999', $result['payment_id']);
        $this->assertSame('pending', $result['classification']);

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $transaction->fresh()->status
        );
    }

    public function test_inspect_summary_rejects_amount_changed_during_bank_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-amount-change',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-amount-change')
            ->andReturnUsing(function () use ($transaction) {
                Transaction::query()
                    ->whereKey($transaction->id)
                    ->update([
                        'amount' => '2000.00',
                    ]);

                return [
                    'Success' => true,
                    'OrderId' => 'attempt-amount-change',
                    'Payments' => [
                        [
                            'PaymentId' => 123456,
                            'Amount' => 100000,
                            'Status' => 'AUTHORIZED',
                        ],
                    ],
                ];
            });

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction changed during reconciliation'
        );

        $service->inspectSummary($transaction, $gateway);
    }

    public function test_inspect_summary_rejects_order_changed_during_bank_request(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $anotherOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'gateway_order_id' => 'attempt-order-change',
            'gateway_transaction_id' => '123456',
            'amount' => '1000.00',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $gateway = Mockery::mock(TinkoffGateway::class);

        $gateway->shouldReceive('checkOrderStatus')
            ->once()
            ->with('attempt-order-change')
            ->andReturnUsing(function () use ($transaction, $anotherOrder) {
                Transaction::query()
                    ->whereKey($transaction->id)
                    ->update([
                        'order_id' => $anotherOrder->id,
                    ]);

                return [
                    'Success' => true,
                    'OrderId' => 'attempt-order-change',
                    'Payments' => [
                        [
                            'PaymentId' => 123456,
                            'Amount' => 100000,
                            'Status' => 'AUTHORIZED',
                        ],
                    ],
                ];
            });

        $service = new TinkoffReconciliationService();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage(
            'Transaction changed during reconciliation'
        );

        $service->inspectSummary($transaction, $gateway);
    }
}

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
}

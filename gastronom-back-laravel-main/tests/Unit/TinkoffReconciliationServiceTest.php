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
}

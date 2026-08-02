<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
    }

    public function test_create_transaction(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_amount' => 0,
            'delivery_cost' => 0,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'unit_price' => 500.00,
        ]);

        $order->refresh();

        $transaction = $this->paymentService->createTransaction(
            $order,
            'card',
            'sberbank'
        );

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 1000.00,
            'gateway' => 'sberbank',
            'status' => TransactionStatus::PENDING->value,
        ]);
    }

    public function test_process_payment_success(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'sberbank',
            'status' => TransactionStatus::PENDING,
        ]);

        $gatewayResponse = [
            'success' => true,
            'transaction_id' => 'ext-txn-123',
            'message' => 'Payment completed',
        ];

        $result = $this->paymentService->processPayment($transaction, $gatewayResponse);

        $this->assertEquals(TransactionStatus::COMPLETED, $result->status);
        $this->assertNotNull($result->processed_at);
        $this->assertEquals('ext-txn-123', $result->gateway_transaction_id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_process_payment_failure(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'sberbank',
            'status' => TransactionStatus::PENDING,
        ]);

        $gatewayResponse = [
            'success' => false,
            'message' => 'Insufficient funds',
        ];

        $result = $this->paymentService->processPayment($transaction, $gatewayResponse);

        $this->assertEquals(TransactionStatus::FAILED, $result->status);
        $this->assertNotNull($result->processed_at);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'failed',
        ]);
    }

    public function test_refund_transaction(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'paid',
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 1000.00,
        ]);

        $result = $this->paymentService->refundTransaction(
            $transaction,
            500.00,
            'Partial refund'
        );

        $this->assertEquals(TransactionStatus::REFUNDED, $result->status);
        $this->assertStringContainsString('Partial refund', $result->notes);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'refunded',
        ]);
    }

    public function test_refund_exceeds_amount_throws(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 500.00,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->paymentService->refundTransaction($transaction, 1000.00);
    }

    public function test_get_customer_transactions(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();

        Order::factory()->count(3)->create(['customer_id' => $customer->id])->each(function ($order) use ($customer) {
            Transaction::factory()->create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
            ]);
        });

        $transactions = $this->paymentService->getCustomerTransactions($customer);

        $this->assertCount(3, $transactions);
    }

    public function test_get_transaction_stats(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 500.00,
        ]);

        Transaction::factory()->completed()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 500.00,
        ]);

        $stats = $this->paymentService->getTransactionStats($customer);

        $this->assertEquals(1, $stats['total_transactions']);
        $this->assertEquals(1, $stats['completed_transactions']);
        $this->assertEquals(0, $stats['failed_transactions']);
        $this->assertEquals(500.00, $stats['total_amount']);
    }
}

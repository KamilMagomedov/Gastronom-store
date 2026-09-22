<?php

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\Gateways\TinkoffGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'https://securepay.tinkoff.ru/v2/Init' => function () use (
                $gateway,
                $webhookPayload,
                $paymentId,
                $paymentUrl
            ) {
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
}
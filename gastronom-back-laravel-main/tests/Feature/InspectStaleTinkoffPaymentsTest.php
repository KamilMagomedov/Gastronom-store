<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectStaleTinkoffPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_lists_only_stale_processing_tinkoff_payments(): void
    {
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'pending',
        ]);

        $stale = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'status' => TransactionStatus::PROCESSING,
            'gateway_order_id' => 'stale-attempt-1',
            'created_at' => now()->subMinutes(20),
        ]);

        $this->assertTrue(
            $stale->fresh()->created_at->lessThanOrEqualTo(
                now()->subMinutes(10)
            ),
            'Stale transaction must be older than 10 minutes.'
        );

        $recent = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'status' => TransactionStatus::PROCESSING,
            'gateway_order_id' => 'recent-attempt-1',
        ]);

        $completed = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'tinkoff',
            'status' => TransactionStatus::COMPLETED,
            'gateway_order_id' => 'completed-attempt-1',
            'created_at' => now()->subMinutes(30),
        ]);

        $otherGateway = Transaction::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'gateway' => 'sberbank',
            'status' => TransactionStatus::PROCESSING,
        ]);

        $otherGateway->timestamps = false;
        $otherGateway->update(['created_at' => now()->subMinutes(40)]);

        $this->artisan('payments:inspect-stale-tinkoff', [
            '--minutes' => 10,
        ])
            ->expectsOutputToContain(
                'Transaction #'.$stale->id.' | Order #'.$order->id
            )
            ->expectsOutput(
                'Found 1 stale Tinkoff payment attempts.'
            )
            ->assertSuccessful();

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $stale->fresh()->status
        );

        $this->assertSame(
            TransactionStatus::PROCESSING,
            $recent->fresh()->status
        );

        $this->assertSame(
            TransactionStatus::COMPLETED,
            $completed->fresh()->status
        );
    }

    public function test_command_rejects_invalid_minutes(): void
    {
        $this->artisan('payments:inspect-stale-tinkoff', [
            '--minutes' => 0,
        ])
            ->expectsOutput('Minutes must be a positive integer.')
            ->assertFailed();
    }
}

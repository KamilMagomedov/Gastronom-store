<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_via_mail(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $deliveryMethod = DeliveryMethod::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'delivery_method_id' => $deliveryMethod->id,
            'total_amount' => 150.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 75.00,
            'total_price' => 150.00,
        ]);

        $notification = new OrderCreatedNotification($order);

        $via = $notification->via($customer);
        $this->assertEquals(['mail'], $via);

        $mailMessage = $notification->toMail($customer);

        $this->assertStringContainsString('Заказ №'.$order->id, $mailMessage->subject);
        $this->assertStringContainsString($order->id, $mailMessage->subject);
    }

    public function test_notification_is_queued(): void
    {
        $reflection = new \ReflectionClass(OrderCreatedNotification::class);

        $this->assertTrue(
            $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class)
        );
    }
}

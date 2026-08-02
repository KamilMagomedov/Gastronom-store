<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiate_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->postJson(route('api.v1.payments.initiate', $order));

        $response->assertStatus(401);
    }

    public function test_initiate_returns_unauthorized_for_other_customer(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        Sanctum::actingAs($customer1, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->postJson(route('api.v1.payments.initiate', $order));

        $response->assertStatus(403);
    }

    public function test_initiate_fails_for_non_online_payment_method(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $paymentMethod = PaymentMethod::factory()->cash()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->postJson(route('api.v1.payments.initiate', $order));

        $response->assertStatus(422);
    }

    public function test_initiate_fails_for_already_paid_order(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => 'paid',
        ]);

        $response = $this->postJson(route('api.v1.payments.initiate', $order));

        $response->assertStatus(422);
    }

    public function test_store_creates_order_and_sends_notification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        $product = Product::factory()->create();
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
        ]);

        $orderData = [
            'delivery_method' => $deliveryMethod->id,
            'payment_method' => $paymentMethod->id,
            'delivery_phone' => '+1234567890',
            'delivery_street' => 'Test Street',
            'delivery_city' => 'Test City',
            'delivery_apartment' => '123',
            'delivery_postal_code' => '12345',
            'delivery_latitude' => '40.71280000',
            'delivery_longitude' => '-74.00600000',
            'delivery_building' => 'A',
            'delivery_entrance' => '5',
            'delivery_floor' => '3',
            'notes' => 'Order notes',
        ];

        $response = $this->postJson(route('api.v1.orders.store'), $orderData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Notification::assertSentTo(
            $customer,
            OrderCreatedNotification::class,
            function (OrderCreatedNotification $notification) use ($customer) {
                return $notification->order->customer_id === $customer->id;
            }
        );
    }

    public function test_webhook_handles_payment_succeeded(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $payload = [
            'gateway' => 'sberbank',
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'test-txn-id-'.$order->id,
                'amount' => [
                    'value' => (string) $order->total_amount,
                    'currency' => 'RUB',
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'customer_id' => (string) $customer->id,
                ],
            ],
        ];

        $response = $this->postJson(route('api.payments.webhook'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'gateway_transaction_id' => 'test-txn-id-'.$order->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_webhook_handles_payment_canceled(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $payload = [
            'gateway' => 'sberbank',
            'event' => 'payment.canceled',
            'object' => [
                'id' => 'test-txn-canceled-'.$order->id,
                'amount' => [
                    'value' => (string) $order->total_amount,
                    'currency' => 'RUB',
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'customer_id' => (string) $customer->id,
                ],
                'cancellation_details' => [
                    'reason' => 'payment_timeout',
                ],
            ],
        ];

        $response = $this->postJson(route('api.payments.webhook'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'gateway_transaction_id' => 'test-txn-canceled-'.$order->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'failed',
        ]);
    }

    public function test_callback_returns_order_status(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson(route('api.v1.payments.callback', $order));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ],
            ]);
    }

    public function test_initiate_with_invalid_payment_method_type(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $paymentMethod = PaymentMethod::factory()->online()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $response = $this->postJson(route('api.v1.payments.initiate', $order), [
            'payment_method_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
    }

    public function test_webhook_handles_sbp_payment_succeeded(): void
    {
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->sbp()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => 'pending',
        ]);

        $payload = [
            'gateway' => 'tinkoff',
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'sbp-txn-'.$order->id,
                'amount' => [
                    'value' => (string) $order->total_amount,
                    'currency' => 'RUB',
                ],
                'payment_method' => [
                    'type' => 'sbp',
                ],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'customer_id' => (string) $customer->id,
                ],
            ],
        ];

        $response = $this->postJson(route('api.payments.webhook'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'gateway_transaction_id' => 'sbp-txn-'.$order->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }
}

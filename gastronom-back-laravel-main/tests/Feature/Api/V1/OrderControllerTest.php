<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $indexRoute;

    protected string $storeRoute;

    protected string $showRoute;

    protected string $updateRoute;

    protected string $cancelRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexRoute = route('api.v1.orders.index');
        $this->storeRoute = route('api.v1.orders.store');
    }

    public function test_index_returns_orders_successfully(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        Order::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'total_amount',
                        'payment_method',
                        'status',
                        'payment_status',
                        'delivered_at',
                        'created_at',
                        'order_summary' => [
                            'image',
                            'product_names',
                            'items_count',
                            'total_price',
                        ],
                    ],
                ],
                'paginator' => [
                    'per_page',
                    'current_page',
                    'last_page',
                    'total',
                    'has_more',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_index_returns_empty_when_no_orders(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(401);
    }

    public function test_show_returns_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson(route('api.v1.orders.show', $order));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'total_amount',
                    'shipping_amount',
                    'delivery_method',
                    'delivery_cost',
                    'delivery_address',
                    'delivery_phone',
                    'payment_method',
                    'status',
                    'payment_status',
                    'delivered_at',
                    'created_at',
                    'products' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'price',
                            'old_price',
                            'sku',
                            'unit',
                            'image',
                            'category' => [
                                'id',
                                'name',
                                'slug',
                                'image',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_show_returns_not_found_for_nonexistent_order(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->getJson(route('api.v1.orders.show', 99999));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_show_returns_unauthorized_for_other_customer_order(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        Sanctum::actingAs($customer1, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->getJson(route('api.v1.orders.show', $order));

        $response->assertStatus(403);
    }

    public function test_show_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson(route('api.v1.orders.show', $order));

        $response->assertStatus(401);
    }

    public function test_store_creates_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
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
            'delivery_notes' => 'Test notes',
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

        $response = $this->postJson($this->storeRoute, $orderData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'total_amount',
                    'shipping_amount',
                    'delivery_method',
                    'delivery_cost',
                    'delivery_address',
                    'delivery_phone',
                    'payment_method',
                    'status',
                    'payment_status',
                    'delivered_at',
                    'created_at',
                ],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'delivery_method_id' => $deliveryMethod->id,
            'payment_method_id' => $paymentMethod->id,
            'delivery_street' => 'Test Street',
            'delivery_city' => 'Test City',
            'delivery_apartment' => '123',
            'delivery_floor' => '3',
        ]);
    }

    public function test_store_validation_failure(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->postJson($this->storeRoute, []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'delivery_method',
                    'payment_method',
                ],
            ]);
    }

    public function test_store_fails_with_empty_cart(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $orderData = [
            'delivery_method' => $deliveryMethod->id,
            'payment_method' => $paymentMethod->id,
        ];

        $response = $this->postJson($this->storeRoute, $orderData);

        $response->assertStatus(422);
    }

    public function test_store_fails_when_stock_insufficient(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 1]);

        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 100.00,
            'total' => 500.00,
        ]);

        $orderData = [
            'delivery_method' => $deliveryMethod->id,
            'payment_method' => $paymentMethod->id,
            'delivery_phone' => '+1234567890',
            'delivery_street' => 'Test Street',
            'delivery_city' => 'Test City',
        ];

        $response = $this->postJson($this->storeRoute, $orderData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Недостаточно товара на складе',
            ])
            ->assertJsonStructure([
                'data' => [
                    'insufficient_items' => [
                        '*' => [
                            'product_id',
                            'product_name',
                            'requested',
                            'available',
                        ],
                    ],
                ],
            ]);

        $insufficientItems = $response->json('data.insufficient_items');
        $this->assertCount(1, $insufficientItems);
        $this->assertEquals($product->id, $insufficientItems[0]['product_id']);
        $this->assertEquals(5, $insufficientItems[0]['requested']);
        $this->assertEquals(1, $insufficientItems[0]['available']);

        $this->assertDatabaseMissing('orders', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_store_stock_is_decremented_on_successful_order(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 100.00,
            'total' => 300.00,
        ]);

        $orderData = [
            'delivery_method' => $deliveryMethod->id,
            'payment_method' => $paymentMethod->id,
            'delivery_phone' => '+1234567890',
            'delivery_street' => 'Test Street',
            'delivery_city' => 'Test City',
        ];

        $response = $this->postJson($this->storeRoute, $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 7, // 10 - 3
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson($this->storeRoute, []);

        $response->assertStatus(401);
    }

    public function test_store_with_cash_payment_does_not_return_payment_url(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->cash()->create();

        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cart = Cart::factory()->create(['customer_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
            'total' => 100.00,
        ]);

        $orderData = [
            'delivery_method' => $deliveryMethod->id,
            'payment_method' => $paymentMethod->id,
            'delivery_phone' => '+1234567890',
            'delivery_street' => 'Test Street',
            'delivery_city' => 'Test City',
        ];

        $response = $this->postJson($this->storeRoute, $orderData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $paymentUrl = $response->json('data.payment_url');
        $this->assertNull($paymentUrl);
    }

    public function test_update_updates_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $updateData = [
            'delivery_phone' => '+0987654321',
            'delivery_notes' => 'Updated notes',
            'delivery_street' => 'Updated Street',
            'delivery_city' => 'Updated City',
            'delivery_apartment' => '456',
            'delivery_postal_code' => '67890',
            'delivery_latitude' => '51.50740000',
            'delivery_longitude' => '-0.12780000',
            'delivery_building' => 'B',
            'delivery_entrance' => '3',
            'delivery_floor' => '5',
            'notes' => 'Updated order notes',
        ];

        $response = $this->putJson(route('api.v1.orders.update', $order), $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'total_amount',
                    'shipping_amount',
                    'delivery_method',
                    'delivery_cost',
                    'delivery_address',
                    'delivery_phone',
                    'payment_method',
                    'status',
                    'payment_status',
                    'delivered_at',
                    'created_at',
                ],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'delivery_phone' => '+0987654321',
            'delivery_street' => 'Updated Street',
            'delivery_city' => 'Updated City',
            'delivery_apartment' => '456',
            'delivery_floor' => '5',
        ]);
    }

    public function test_update_validation_failure(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $updateData = [
            'delivery_floor' => str_repeat('a', 11), // Too long
        ];

        $response = $this->putJson(route('api.v1.orders.update', $order), $updateData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'delivery_floor',
                ],
            ]);
    }

    public function test_update_validation_failure_floor_too_long(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $updateData = [
            'delivery_floor' => str_repeat('a', 11), // More than 10 characters
        ];

        $response = $this->putJson(route('api.v1.orders.update', $order), $updateData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'delivery_floor',
                ],
            ]);
    }

    public function test_update_returns_unauthorized_for_other_customer_order(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        Sanctum::actingAs($customer1, guard: 'customers');

        $order = Order::factory()->create(['customer_id' => $customer2->id]);

        $updateData = ['notes' => 'Test'];

        $response = $this->putJson(route('api.v1.orders.update', $order), $updateData);

        $response->assertStatus(403);
    }

    public function test_update_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->putJson(route('api.v1.orders.update', $order), []);

        $response->assertStatus(401);
    }

    // Cancel tests
    public function test_cancel_cancels_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $cancelData = ['reason' => 'Changed my mind'];

        $response = $this->putJson(route('api.v1.orders.cancel', $order), $cancelData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_cancel_validation_failure(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $cancelData = ['reason' => str_repeat('a', 501)]; // Too long

        $response = $this->putJson(route('api.v1.orders.cancel', $order), $cancelData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'reason',
                ],
            ]);
    }

    public function test_cancel_fails_for_completed_order(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::COMPLETED->value,
        ]);

        $cancelData = ['reason' => 'Test'];

        $response = $this->putJson(route('api.v1.orders.cancel', $order), $cancelData);

        $response->assertStatus(400);
    }

    public function test_cancel_returns_unauthorized_for_other_customer_order(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        Sanctum::actingAs($customer1, guard: 'customers');

        $order = Order::factory()->create([
            'customer_id' => $customer2->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $cancelData = ['reason' => 'Test'];

        $response = $this->putJson(route('api.v1.orders.cancel', $order), $cancelData);

        $response->assertStatus(403);
    }

    public function test_cancel_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->putJson(route('api.v1.orders.cancel', $order), []);

        $response->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderRepeatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected Order $order;

    protected Product $availableProduct;

    protected Product $outOfStockProduct;

    protected Product $inactiveProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();

        $this->availableProduct = Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 10,
            'price' => 100.00,
            'unit' => 'pcs',
        ]);

        $this->outOfStockProduct = Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 0,
            'price' => 50.00,
            'unit' => 'pcs',
        ]);

        $this->inactiveProduct = Product::factory()->create([
            'is_active' => false,
            'stock_quantity' => 5,
            'price' => 75.00,
            'unit' => 'pcs',
        ]);

        $deliveryMethod = DeliveryMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'delivery_method_id' => $deliveryMethod->id,
            'payment_method_id' => $paymentMethod->id,
            'status' => 'delivered',
            'total_amount' => 450.00,
            'subtotal' => 0,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->availableProduct->id,
            'product_name' => $this->availableProduct->name,
            'product_sku' => $this->availableProduct->sku,
            'quantity' => 3,
            'unit_price' => $this->availableProduct->price,
            'total_price' => 300.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->outOfStockProduct->id,
            'product_name' => $this->outOfStockProduct->name,
            'product_sku' => $this->outOfStockProduct->sku,
            'quantity' => 5, // More than stock
            'unit_price' => $this->outOfStockProduct->price,
            'total_price' => 250.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->inactiveProduct->id,
            'product_name' => $this->inactiveProduct->name,
            'product_sku' => $this->inactiveProduct->sku,
            'quantity' => 2,
            'unit_price' => $this->inactiveProduct->price,
            'total_price' => 150.00,
        ]);
    }

    public function test_check_order_for_repeat_mixed_availability(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'available_items' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_name',
                            'product_slug',
                            'product_sku',
                            'product_image',
                            'requested_quantity',
                            'available_quantity',
                            'price',
                            'old_price',
                            'unit',
                            'is_available',
                            'shortage',
                        ],
                    ],
                    'unavailable_items' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_name',
                            'product_sku',
                            'requested_quantity',
                            'reason',
                        ],
                    ],
                    'can_repeat',
                    'total_available_items',
                    'total_unavailable_items',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $this->order->id,
                    'can_repeat' => false,
                    'total_available_items' => 1,
                    'total_unavailable_items' => 2,
                ],
            ]);

        $availableItems = $response->json('data.available_items');
        $this->assertCount(1, $availableItems);
        $this->assertEquals($this->availableProduct->id, $availableItems[0]['product_id']);
        $this->assertEquals(3, $availableItems[0]['requested_quantity']);
        $this->assertEquals(10, $availableItems[0]['available_quantity']);
        $this->assertTrue($availableItems[0]['is_available']);
        $this->assertEquals(0, $availableItems[0]['shortage']);

        $unavailableItems = $response->json('data.unavailable_items');
        $this->assertCount(2, $unavailableItems);

        $outOfStockItem = collect($unavailableItems)->firstWhere('reason', 'out_of_stock');
        $this->assertEquals($this->outOfStockProduct->id, $outOfStockItem['product_id']);
        $this->assertEquals(5, $outOfStockItem['requested_quantity']);

        $inactiveItem = collect($unavailableItems)->firstWhere('reason', 'inactive');
        $this->assertEquals($this->inactiveProduct->id, $inactiveItem['product_id']);
        $this->assertEquals(2, $inactiveItem['requested_quantity']);
    }

    /**
     * Test checking order for repeat when all items are available
     */
    public function test_check_order_for_repeat_all_available(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $this->outOfStockProduct->update(['stock_quantity' => 10]);

        $this->inactiveProduct->update(['is_active' => true]);

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $this->order->id,
                    'can_repeat' => true,
                    'total_available_items' => 3,
                    'total_unavailable_items' => 0,
                    'unavailable_items' => [],
                ],
            ]);
    }

    /**
     * Test checking order for repeat when order doesn't exist
     */
    public function test_check_order_for_repeat_order_not_found(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => 999,
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test checking order for repeat without authentication
     */
    public function test_check_order_for_repeat_unauthorized(): void
    {
        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(401);
    }

    /**
     * Test checking order for repeat with invalid order ID
     */
    public function test_check_order_for_repeat_invalid_order_id(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => 'invalid',
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test checking order for repeat with non-existent order ID
     */
    public function test_check_order_for_repeat_non_existent_order_id(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => 99999,
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test repeating order when all items are available
     */
    public function test_repeat_order_success(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $this->outOfStockProduct->update(['stock_quantity' => 10]);
        $this->inactiveProduct->update(['is_active' => true]);

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.store',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

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

            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->assertEquals(7, $this->availableProduct->fresh()->stock_quantity); // 10 - 3
        $this->assertEquals(5, $this->outOfStockProduct->fresh()->stock_quantity); // 10 - 5
        $this->assertEquals(3, $this->inactiveProduct->fresh()->stock_quantity); // 5 - 2

        $newOrderId = $response->json('data.id');
        $this->assertDatabaseHas('order_items', [
            'order_id' => $newOrderId,
            'product_id' => $this->availableProduct->id,
            'quantity' => 3,
        ]);
    }

    /**
     * Test repeating order when some items are unavailable
     */
    public function test_repeat_order_unavailable_items(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.store',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Order cannot be repeated: some items are unavailable',
            ]);
    }

    /**
     * Test repeating order that doesn't exist
     */
    public function test_repeat_order_not_found(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.store',
                [
                    'order_id' => 999,
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test repeating order without authentication
     */
    public function test_repeat_order_unauthorized(): void
    {
        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.store',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(401);
    }

    /**
     * Test repeating order with invalid order ID
     */
    public function test_repeat_order_invalid_order_id(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.store',
                [
                    'order_id' => 'invalid',
                ]
            )
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test checking order for repeat with empty order
     */
    public function test_check_order_for_repeat_empty_order(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $emptyOrder = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'delivered',
            'total_amount' => 0.00,
        ]);

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $emptyOrder->id,
                ]
            )
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $emptyOrder->id,
                    'can_repeat' => true,
                    'total_available_items' => 0,
                    'total_unavailable_items' => 0,
                    'available_items' => [],
                    'unavailable_items' => [],
                ],
            ]);
    }

    /**
     * Test checking order for repeat with order items that have exactly enough stock
     */
    public function test_check_order_for_repeat_exact_stock(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $this->outOfStockProduct->update(['stock_quantity' => 5]);

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(200);

        $outOfStockItem = collect($response->json('data.available_items'))
            ->firstWhere('product_id', $this->outOfStockProduct->id);

        $this->assertEquals(5, $outOfStockItem['available_quantity']);
        $this->assertEquals(5, $outOfStockItem['requested_quantity']);
        $this->assertTrue($outOfStockItem['is_available']);
        $this->assertEquals(0, $outOfStockItem['shortage']);
    }

    /**
     * Test checking order for repeat with missing product (product was deleted)
     */
    public function test_check_order_for_repeat_missing_product(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $this->inactiveProduct->delete();

        $response = $this->postJson(
            route(
                'api.v1.orders-repeat.check',
                [
                    'order_id' => $this->order->id,
                ]
            )
        );

        $response->assertStatus(200);

        $unavailableItems = $response->json('data.unavailable_items');
        $missingItem = collect($unavailableItems)->firstWhere('product_id', $this->inactiveProduct->id);
        $this->assertNull($missingItem, 'Missing item should be found in unavailable items');
    }
}

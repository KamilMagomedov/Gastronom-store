<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $indexRoute;

    protected string $storeRoute;

    protected string $clearRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexRoute = route('api.v1.carts.index');
        $this->storeRoute = route('api.v1.carts.store');
        $this->clearRoute = route('api.v1.carts-clear');
    }

    /**
     * Test successful get cart
     */
    public function test_index_returns_cart_successfully(): void
    {
        $sessionId = 'abc123';
        $cart = Cart::factory()->create([
            'session_id' => $sessionId,
            'total_amount' => 100.00,
            'total_items' => 2,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
        ]);

        $response = $this->getJson($this->indexRoute.'?session_id='.$sessionId);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_amount',
                    'total_items',
                    'expires_at',
                    'session_id',
                    'items' => [
                        '*' => [
                            'quantity',
                            'price',
                            'total',
                            'product',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_amount' => '100.00',
                    'total_items' => 2,
                ],
            ]);
    }

    /**
     * Test successful add product to cart
     * Note: This test may fail if product availability logic has bugs
     */
    public function test_store_adds_product_to_cart_successfully(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'in_stock' => true,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson($this->storeRoute, [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
    }

    /**
     * Test store validation failure
     */
    public function test_store_validation_failure(): void
    {
        $response = $this->postJson($this->storeRoute, []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'product_id',
                    'quantity',
                ],
            ]);
    }

    /**
     * Test store with invalid product id
     */
    public function test_store_with_invalid_product_id(): void
    {
        $response = $this->postJson($this->storeRoute, [
            'product_id' => 99999,
            'quantity' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'product_id',
                ],
            ]);
    }

    /**
     * Test store with invalid quantity
     */
    public function test_store_with_invalid_quantity(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->postJson($this->storeRoute, [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'quantity',
                ],
            ]);
    }

    /**
     * Test store with quantity exceeding max
     */
    public function test_store_with_quantity_exceeding_max(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->postJson($this->storeRoute, [
            'product_id' => $product->id,
            'quantity' => 101,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'quantity',
                ],
            ]);
    }

    /**
     * Test successful remove product from cart
     */
    public function test_destroy_removes_product_from_cart_successfully(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'in_stock' => true,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $sessionId = 'abc123';
        $cart = Cart::factory()->create([
            'session_id' => $sessionId,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson(route('api.v1.carts.destroy', $product->id), [
            'session_id' => $sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('carts', [
            'total_amount' => 0,
            'total_items' => 0,
        ]);
    }

    /**
     * Test destroy when product not found in cart
     */
    public function test_destroy_returns_error_when_product_not_found_in_cart(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $sessionId = 'abc123';
        Cart::factory()->create([
            'session_id' => $sessionId,
        ]);

        $response = $this->deleteJson(route('api.v1.carts.destroy', $product->id), [
            'session_id' => $sessionId,
        ]);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
                'message',
            ])
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Product not found in cart',
                ],
            ]);
    }

    /**
     * Test successful clear cart
     */
    public function test_clear_clears_cart_successfully(): void
    {
        $sessionId = 'abc123';
        $cart = Cart::factory()->create([
            'session_id' => $sessionId,
        ]);

        CartItem::factory()->count(3)->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->getJson($this->clearRoute.'?session_id='.$sessionId);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $cart->refresh();
        $this->assertEquals(0, $cart->total_items);
        $this->assertEquals(0, $cart->total_amount);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('carts', [
            'total_amount' => 0,
            'total_items' => 0,
        ]);
    }

    /**
     * Test authenticated user can get their cart
     */
    public function test_authenticated_user_can_get_cart(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 150.00,
            'total_items' => 3,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'quantity' => 3,
            'price' => 50.00,
            'total' => 150.00,
        ]);

        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_amount',
                    'total_items',
                    'expires_at',
                    'items' => [
                        '*' => [
                            'quantity',
                            'price',
                            'total',
                            'product',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_amount' => '150.00',
                    'total_items' => 3,
                ],
            ]);
    }

    /**
     * Test authenticated user can add product to cart
     */
    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'in_stock' => true,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson($this->storeRoute, [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('carts', [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Test authenticated user can remove product from cart
     */
    public function test_authenticated_user_can_remove_product_from_cart(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'in_stock' => true,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson(route('api.v1.carts.destroy', $product->id));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('carts', [
            'total_items' => 0,
            'total_amount' => 0,
        ]);
    }

    /**
     * Test authenticated user can clear their cart
     */
    public function test_authenticated_user_can_clear_cart(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        CartItem::factory()->count(3)->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->getJson($this->clearRoute);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $cart->refresh();
        $this->assertEquals(0, $cart->total_items);
        $this->assertEquals(0, $cart->total_amount);
    }

    /**
     * Test authenticated user cannot access another user's cart
     */
    public function test_authenticated_user_cannot_access_another_users_cart(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        Sanctum::actingAs($customer1, guard: 'customers');

        $cart = Cart::factory()->create([
            'customer_id' => $customer2->id,
            'total_amount' => 100.00,
            'total_items' => 2,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_amount' => '0.00',
                    'total_items' => 0,
                    'expires_at' => true,
                    'session_id' => null,
                    'items' => [],
                ],
                'success' => true,
            ]);
    }

    /**
     * Test authenticated user removes product from their own cart
     */
    public function test_authenticated_user_cannot_remove_product_from_another_users_cart(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        Sanctum::actingAs($customer1, guard: 'customers');

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer2->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson(route('api.v1.carts.destroy', $product->id));

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Cart not found',
                ],
            ]);
    }

    /**
     * Test authenticated user creates new cart if none exists
     */
    public function test_authenticated_user_creates_new_cart_if_none_exists(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->getJson($this->indexRoute);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('carts', [
            'customer_id' => $customer->id,
        ]);
    }
}

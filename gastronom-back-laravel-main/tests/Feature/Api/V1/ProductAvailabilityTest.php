<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'test-product',
            'stock_quantity' => 10,
            'in_stock' => true,
            'is_active' => true,
        ]);

        $this->route = route('api.v1.products.availability', ['product' => 'test-product']);
    }

    public function test_returns_availability_for_active_product(): void
    {
        $response = $this->getJson($this->route);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'slug',
                    'name',
                    'in_stock',
                    'stock_quantity',
                    'is_active',
                    'available',
                ],
                'success',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'test-product',
                    'in_stock' => true,
                    'stock_quantity' => 10,
                    'is_active' => true,
                    'available' => true,
                ],
            ]);
    }

    public function test_returns_not_available_for_inactive_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'inactive-product',
            'is_active' => false,
            'in_stock' => true,
            'stock_quantity' => 5,
        ]);

        $response = $this->getJson(route('api.v1.products.availability', ['product' => 'inactive-product']));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'available' => false,
                    'is_active' => false,
                ],
            ]);
    }

    public function test_returns_not_available_when_out_of_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'out-of-stock',
            'stock_quantity' => 0,
            'in_stock' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('api.v1.products.availability', ['product' => 'out-of-stock']));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'available' => false,
                    'in_stock' => false,
                    'stock_quantity' => 0,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson(route('api.v1.products.availability', ['product' => 'nonexistent-slug']));

        $response->assertStatus(404);
    }

    public function test_returns_quantity_check_when_quantity_param_provided(): void
    {
        $response = $this->getJson($this->route.'?quantity=5');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'requested_quantity' => 5,
                    'is_enough' => true,
                    'shortage' => 0,
                    'status' => 'available',
                ],
            ]);
    }

    public function test_returns_not_enough_when_quantity_exceeds_stock(): void
    {
        $response = $this->getJson($this->route.'?quantity=15');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'requested_quantity' => 15,
                    'is_enough' => false,
                    'shortage' => 5,
                    'status' => 'out_of_stock',
                ],
            ]);
    }

    public function test_returns_inactive_status_for_inactive_product_with_quantity(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'inactive-qty',
            'is_active' => false,
            'stock_quantity' => 10,
        ]);

        $response = $this->getJson(route('api.v1.products.availability', ['product' => 'inactive-qty']).'?quantity=3');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_active' => false,
                    'available' => false,
                    'status' => 'inactive',
                ],
            ]);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrequentlyPurchasedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test categories
        $this->categories = Category::factory(5)->create();

        // Create test products
        $this->products = Product::factory(15)->create([
            'category_id' => $this->categories->random()->id,
        ]);

        // Create product sales with different quantities
        foreach ($this->products as $index => $product) {
            ProductSale::create([
                'product_id' => $product->id,
                'total_quantity' => 15 - $index, // Higher quantity for first products
                'total_revenue' => rand(100, 1000),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_popular_offers_api_returns_success_response(): void
    {
        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'old_price',
                        'sku',
                        'image',
                        'category' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                ],
                'success',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_popular_offers_returns_correct_data_structure(): void
    {
        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(10, count($data)); // Should return max 10 items

        if (! empty($data)) {
            $firstProduct = $data[0];
            $this->assertArrayHasKey('id', $firstProduct);
            $this->assertArrayHasKey('name', $firstProduct);
            $this->assertArrayHasKey('slug', $firstProduct);
            $this->assertArrayHasKey('price', $firstProduct);
            $this->assertArrayHasKey('old_price', $firstProduct);
            $this->assertArrayHasKey('sku', $firstProduct);
            $this->assertArrayHasKey('image', $firstProduct);
            $this->assertArrayHasKey('category', $firstProduct);

            // Check category structure
            $category = $firstProduct['category'];
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertArrayHasKey('slug', $category);
        }
    }

    public function test_popular_offers_orders_by_total_quantity_desc(): void
    {
        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');

        if (count($data) > 1) {
            // Get product IDs from response
            $productIds = collect($data)->pluck('id')->toArray();

            // Get corresponding product sales ordered by quantity
            $expectedOrder = ProductSale::whereIn('product_id', $productIds)
                ->orderByDesc('total_quantity')
                ->pluck('product_id')
                ->toArray();

            $this->assertEquals($expectedOrder, $productIds);
        }
    }

    public function test_popular_offers_limits_to_10_items(): void
    {
        $additionalProducts = Product::factory(5)->create([
            'category_id' => $this->categories->random()->id,
        ]);

        foreach ($additionalProducts as $product) {
            ProductSale::create([
                'product_id' => $product->id,
                'total_quantity' => rand(1, 5),
                'total_revenue' => rand(100, 1000),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));
    }

    public function test_popular_offers_excludes_products_without_sales(): void
    {
        $productWithoutSales = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
        ]);

        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');
        $productIds = collect($data)->pluck('id')->toArray();

        $this->assertNotContains($productWithoutSales->id, $productIds);
    }

    public function test_popular_offers_includes_category_relationship(): void
    {
        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');

        if (! empty($data)) {
            $firstProduct = $data[0];
            $this->assertNotNull($firstProduct['category']);
            $this->assertIsArray($firstProduct['category']);
            $this->assertArrayHasKey('id', $firstProduct['category']);
            $this->assertArrayHasKey('name', $firstProduct['category']);
            $this->assertArrayHasKey('slug', $firstProduct['category']);
        }
    }

    public function test_popular_offers_handles_empty_database(): void
    {
        ProductSale::query()->delete();
        Product::query()->delete();

        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'success' => true,
            ]);
    }

    public function test_popular_offers_returns_correct_price_format(): void
    {
        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');

        if (! empty($data)) {
            $firstProduct = $data[0];

            // Price should be a string (from database decimal)
            $this->assertIsString($firstProduct['price']);

            // Old price can be null
            if ($firstProduct['old_price'] !== null) {
                $this->assertIsString($firstProduct['old_price']);
            }

            // SKU should be a string
            $this->assertIsString($firstProduct['sku']);
        }
    }

    public function test_popular_offers_with_inactive_products(): void
    {
        ProductSale::query()->delete();
        Product::query()->delete();

        $inactiveProduct = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
            'is_active' => false,
        ]);

        ProductSale::create([
            'product_id' => $inactiveProduct->id,
            'total_quantity' => 100, // High quantity to test if it appears
            'total_revenue' => 1000,
            'updated_at' => now(),
        ]);

        $response = $this->getJson(route('api.v1.home.frequently-purchased'));

        $data = $response->json('data');
        $productIds = collect($data)->pluck('id')->toArray();

        $this->assertNotContains($inactiveProduct->id, $productIds);
    }
}

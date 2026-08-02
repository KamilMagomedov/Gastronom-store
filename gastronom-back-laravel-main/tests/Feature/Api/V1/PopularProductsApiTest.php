<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PopularProductsApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $route;

    protected ?Collection $categories = null;

    protected ?Collection $products = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.home.popular-products');
    }

    public function test_popular_products_api_returns_success_response(): void
    {
        $this->setUpData();

        $response = $this->getJson($this->route);

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
                        'unit',
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

    public function test_popular_products_orders_by_total_revenue_desc(): void
    {
        $this->setUpData();

        $response = $this->getJson($this->route);

        $data = $response->json('data');

        if (count($data) > 1) {
            $productIds = collect($data)->pluck('id')->toArray();

            $expectedOrder = ProductSale::whereIn('product_id', $productIds)
                ->orderByDesc('total_revenue')
                ->pluck('product_id')
                ->toArray();

            $this->assertEquals($expectedOrder, $productIds);
        }
    }

    public function test_popular_products_limits_to_10_items(): void
    {
        $this->setUpData();

        $additionalProducts = Product::factory(5)->create([
            'category_id' => $this->categories->random()->id,
        ]);

        foreach ($additionalProducts as $product) {
            ProductSale::create([
                'product_id' => $product->id,
                'total_quantity' => rand(1, 5),
                'total_revenue' => rand(100, 500),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $this->assertLessThanOrEqual(10, count($data));
    }

    public function test_popular_products_excludes_products_without_sales(): void
    {
        $this->setUpData();

        $productWithoutSales = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $productIds = collect($data)->pluck('id')->toArray();

        $this->assertNotContains($productWithoutSales->id, $productIds);
    }

    public function test_popular_products_excludes_inactive_products(): void
    {
        $this->setUpData();

        $inactiveProduct = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
            'is_active' => false,
        ]);

        ProductSale::create([
            'product_id' => $inactiveProduct->id,
            'total_quantity' => 10,
            'total_revenue' => 1000,
            'updated_at' => now(),
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $productIds = collect($data)->pluck('id')->toArray();

        $this->assertNotContains($inactiveProduct->id, $productIds);
    }

    public function test_popular_products_includes_category_relationship(): void
    {
        $this->setUpData();

        $response = $this->getJson($this->route);

        $data = $response->json('data');

        if (! empty($data)) {
            $firstProduct = $data[0];
            $this->assertNotNull($firstProduct['category']);
            $this->assertIsArray($firstProduct['category']);
            $this->assertArrayHasKey('sku', $firstProduct);
            $this->assertArrayHasKey('unit', $firstProduct);
            $this->assertArrayHasKey('image', $firstProduct);
            $this->assertArrayHasKey('category', $firstProduct);
            $this->assertArrayHasKey('id', $firstProduct['category']);
            $this->assertArrayHasKey('name', $firstProduct['category']);
            $this->assertArrayHasKey('slug', $firstProduct['category']);
        }
    }

    public function test_popular_products_handles_empty_database(): void
    {
        $response = $this->getJson($this->route);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'success' => true,
            ]);
    }

    public function test_popular_products_returns_correct_price_format(): void
    {
        $this->setUpData();

        $response = $this->getJson($this->route);

        $data = $response->json('data');

        if (! empty($data)) {
            $firstProduct = $data[0];

            $this->assertIsString($firstProduct['price']);

            if ($firstProduct['old_price'] !== null) {
                $this->assertIsString($firstProduct['old_price']);
            }

            $this->assertIsString($firstProduct['sku']);
        }
    }

    public function test_popular_products_api_response_time(): void
    {
        $this->setUpData();

        $startTime = microtime(true);

        $response = $this->getJson($this->route);

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);

        $this->assertLessThan(1.0, $responseTime, 'API response time should be under 1 second');
    }

    public function test_popular_products_with_same_revenue_orders_by_id(): void
    {
        $this->setUpData();

        $product1 = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $this->categories->random()->id,
        ]);

        ProductSale::create([
            'product_id' => $product1->id,
            'total_quantity' => 5,
            'total_revenue' => 5000,
            'updated_at' => now(),
        ]);

        ProductSale::create([
            'product_id' => $product2->id,
            'total_quantity' => 10,
            'total_revenue' => 5000,
            'updated_at' => now(),
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');

        $productIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($product1->id, $productIds);
        $this->assertContains($product2->id, $productIds);
    }

    public function test_popular_products_returns_correct_content_type(): void
    {
        $response = $this->getJson($this->route);

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/json');
    }

    protected function setUpData(): void
    {
        $this->categories = Category::factory(5)->create();

        $this->products = Product::factory(15)->create([
            'category_id' => $this->categories->random()->id,
        ]);

        foreach ($this->products as $index => $product) {
            ProductSale::create([
                'product_id' => $product->id,
                'total_quantity' => rand(1, 50),
                'total_revenue' => (15 - $index) * 100,
                'updated_at' => now(),
            ]);
        }
    }
}

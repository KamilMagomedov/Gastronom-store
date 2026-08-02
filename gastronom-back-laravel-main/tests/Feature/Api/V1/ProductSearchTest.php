<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Strategies\SearchEloquentStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private SearchEloquentStrategy $strategy;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.search-products');
    }

    public function test_search_products_by_name()
    {
        Product::factory()->create(['name' => 'iPhone 15 Pro']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'iPhone 14']);

        $response = $this->postJson($this->route, [
            'query' => 'iphone',
            'limit' => 10,
        ]);

        $response->assertOk();

        $this->assertEquals(2, $response->json('paginator')['total']);

        $items = $response->json('data');

        $this->assertEquals('iPhone 14', $items[0]['name']);
        $this->assertEquals('iPhone 15 Pro', $items[1]['name']);
    }

    public function test_search_products_by_sku()
    {
        Product::factory()->create(['sku' => 'IPHONE-15-PRO-256']);
        Product::factory()->create(['sku' => 'SAMSUNG-GALAXY-S24']);

        $response = $this->postJson($this->route, [
            'query' => 'IPHONE-15',
        ]);

        $response->assertOk();

        $this->assertEquals(1, $response->json('paginator')['total']);
        $items = $response->json('data');

        $this->assertEquals('IPHONE-15-PRO-256', $items[0]['sku']);
    }

    public function test_search_products_case_insensitive()
    {
        Product::factory()->create(['name' => 'iPhone 15 Pro']);

        $response = $this->postJson($this->route, [
            'query' => 'IPHONE',
        ]);

        $response->assertOk();

        $this->assertEquals(1, $response->json('paginator')['total']);
        $items = $response->json('data');

        $this->assertEquals('iPhone 15 Pro', $items[0]['name']);
    }

    public function test_search_products_by_category()
    {
        $electronics = Category::factory()->create(['name' => 'Electronics']);
        $clothing = Category::factory()->create(['name' => 'Clothing']);

        Product::factory()->create([
            'name' => 'Smartphone',
            'category_id' => $electronics->id,
        ]);

        Product::factory()->create([
            'name' => 'T-Shirt',
            'category_id' => $clothing->id,
        ]);

        $response = $this->postJson($this->route, [
            'category' => $electronics->id,
        ]);

        $response->assertOk();

        $this->assertEquals(1, $response->json('paginator')['total']);

        $items = $response->json('data');

        $this->assertEquals('Electronics', $items[0]['category']['name']);
    }

    public function test_search_products_pagination()
    {
        $category = Category::factory()
            ->has(Product::factory()->count(25), 'products')
            ->create();

        $response = $this->postJson($this->route, [
            'category' => $category->id,
            'limit' => 100,
        ]);

        $response->assertOk();

        $paginator = $response->json('paginator');

        $this->assertEquals(25, $paginator['total']);
        $this->assertEquals(1, $paginator['current_page']);
        $this->assertEquals(100, $paginator['per_page']);
    }

    public function test_search_products_returns_correct_structure()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'old_price' => 149.99,
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'unit' => 'шт',
        ]);

        $response = $this->postJson($this->route);

        $items = $response->json('data');
        $item = $items[0];

        $this->assertEquals($product->id, $item['id']);
        $this->assertEquals('Test Product', $item['name']);
        $this->assertEquals(99.99, $item['price']);
        $this->assertEquals(149.99, $item['old_price']);
        $this->assertEquals('TEST-001', $item['sku']);
        $this->assertEquals('шт', $item['unit']);
        $this->assertArrayHasKey('image', $item);
        $this->assertEquals($category->id, $item['category']['id']);
        $this->assertEquals($category->name, $item['category']['name']);
    }

    public function test_search_products_no_results()
    {
        Product::factory()->create(['name' => 'iPhone']);

        $response = $this->postJson($this->route, [
            'query' => 'Nonexistent Product',
        ]);

        $paginator = $response->json('paginator');
        $data = $response->json('data');

        $this->assertEquals(0, $paginator['total']);
        $this->assertCount(0, $data);
    }

    public function test_search_products_empty_query_returns_all()
    {
        Product::factory()->count(5)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->postJson($this->route);

        $this->assertEquals(5, count($response->json('data')));
    }

    public function test_search_products_empty_query_in_stock()
    {
        Product::factory()->count(5)->create(['in_stock' => 1]);
        Product::factory()->create(['in_stock' => 0]);

        $response = $this->postJson($this->route, [
            'in_stock' => 1,
        ]);

        $this->assertEquals(5, count($response->json('data')));
    }

    public function test_search_products_not_in_stock()
    {
        Product::factory()->count(5)->create(['in_stock' => 1]);
        Product::factory()->create(['in_stock' => 0]);

        $response = $this->postJson($this->route, [
            'in_stock' => 0,
        ]);

        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_search_products_return_all()
    {
        Product::factory()->count(5)->create(['in_stock' => 1]);
        Product::factory()->create(['in_stock' => 0]);

        $response = $this->postJson($this->route);

        $this->assertEquals(6, count($response->json('data')));
    }

    public function test_search_products_ordering()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Apple Product']);
        Product::factory()->create(['name' => 'Banana Product']);

        $response = $this->postJson($this->route);

        $items = $response->json('data');

        $this->assertEquals('Apple Product', $items[0]['name']);
        $this->assertEquals('Banana Product', $items[1]['name']);
        $this->assertEquals('Zebra Product', $items[2]['name']);
    }

    public function test_search_products_ordering_desc()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Apple Product']);
        Product::factory()->create(['name' => 'Banana Product']);

        $response = $this->postJson($this->route.'?sort[name]=desc');

        $items = $response->json('data');

        $this->assertEquals('Zebra Product', $items[0]['name']);
        $this->assertEquals('Banana Product', $items[1]['name']);
        $this->assertEquals('Apple Product', $items[2]['name']);
    }

    public function test_search_products_with_text_and_category_filter()
    {
        $electronics = Category::factory()->create(['name' => 'Electronics']);
        $clothing = Category::factory()->create(['name' => 'Clothing']);

        Product::factory()->create([
            'name' => 'iPhone',
            'category_id' => $electronics->id,
        ]);
        Product::factory()->create([
            'name' => 'Samsung Phone',
            'category_id' => $electronics->id,
        ]);
        Product::factory()->create([
            'name' => 'iPhone Case',
            'category_id' => $clothing->id,
        ]);

        $response = $this->postJson($this->route, [
            'query' => 'iPhone',
            'category' => $electronics->id,
        ]);

        $paginator = $response->json('paginator');
        $items = $response->json('data');

        $this->assertEquals(1, $paginator['total']);
        $this->assertEquals('iPhone', $items[0]['name']);
    }

    public function test_search_products_by_price_range()
    {
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 50.00]);
        Product::factory()->create(['name' => 'Medium Product', 'price' => 150.00]);
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 500.00]);

        $response = $this->postJson($this->route, [
            'price_from' => 100,
            'price_to' => 200,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('paginator')['total']);

        $items = $response->json('data');
        $this->assertEquals('Medium Product', $items[0]['name']);
    }

    public function test_search_products_by_price_from_only()
    {
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 50.00]);
        Product::factory()->create(['name' => 'Medium Product', 'price' => 150.00]);
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 500.00]);

        $response = $this->postJson($this->route, [
            'price_from' => 100,
        ]);

        $response->assertOk();
        $this->assertEquals(2, $response->json('paginator')['total']);

        $items = $response->json('data');
        $this->assertEquals('Expensive Product', $items[0]['name']);
        $this->assertEquals('Medium Product', $items[1]['name']);
    }

    public function test_search_products_by_price_to_only()
    {
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 50.00]);
        Product::factory()->create(['name' => 'Medium Product', 'price' => 150.00]);
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 500.00]);

        $response = $this->postJson($this->route, [
            'price_to' => 100,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('paginator')['total']);

        $items = $response->json('data');
        $this->assertEquals('Cheap Product', $items[0]['name']);
    }

    public function test_search_products_sort_by_price_asc()
    {
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 500.00]);
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 50.00]);
        Product::factory()->create(['name' => 'Medium Product', 'price' => 150.00]);

        $response = $this->postJson($this->route.'?sort[price]=asc');

        $response->assertOk();
        $items = $response->json('data');

        $this->assertEquals('Cheap Product', $items[0]['name']);
        $this->assertEquals('Medium Product', $items[1]['name']);
        $this->assertEquals('Expensive Product', $items[2]['name']);
    }

    public function test_search_products_sort_by_price_desc()
    {
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 500.00]);
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 50.00]);
        Product::factory()->create(['name' => 'Medium Product', 'price' => 150.00]);

        $response = $this->postJson($this->route.'?sort[price]=desc');

        $response->assertOk();
        $items = $response->json('data');

        $this->assertEquals('Expensive Product', $items[0]['name']);
        $this->assertEquals('Medium Product', $items[1]['name']);
        $this->assertEquals('Cheap Product', $items[2]['name']);
    }

    public function test_search_products_sort_by_name_desc()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Apple Product']);
        Product::factory()->create(['name' => 'Banana Product']);

        $response = $this->postJson($this->route, [
            'sort' => [
                'name' => 'desc',
            ],
        ]);

        $response->assertOk();
        $items = $response->json('data');

        $this->assertEquals('Zebra Product', $items[0]['name']);
        $this->assertEquals('Banana Product', $items[1]['name']);
        $this->assertEquals('Apple Product', $items[2]['name']);
    }

    public function test_search_products_sort_by_name_asc()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Apple Product']);
        Product::factory()->create(['name' => 'Banana Product']);

        $response = $this->postJson($this->route, [
            'sort' => [
                'name' => 'asc',
            ],
        ]);

        $response->assertOk();

        $items = $response->json('data');

        $this->assertEquals('Apple Product', $items[0]['name']);
        $this->assertEquals('Banana Product', $items[1]['name']);
        $this->assertEquals('Zebra Product', $items[2]['name']);
    }

    public function test_search_products_combined_filters()
    {
        $category = Category::factory()->create(['name' => 'Electronics']);

        Product::factory()->create([
            'name' => 'Cheap In Stock Phone',
            'price' => 100.00,
            'in_stock' => true,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Expensive In Stock Phone',
            'price' => 500.00,
            'in_stock' => true,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Medium Out of Stock Phone',
            'price' => 200.00,
            'in_stock' => false,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Cheap Out of Stock Phone',
            'price' => 50.00,
            'in_stock' => false,
            'category_id' => $category->id,
        ]);

        $response = $this->postJson($this->route, [
            'query' => 'Phone',
            'category' => $category->id,
            'price_from' => 50,
            'price_to' => 300,
            'in_stock' => true,
            'sort' => [
                'price' => 'asc',
            ],
        ]);

        $response->assertOk();

        $this->assertEquals(1, $response->json('paginator')['total']);

        $items = $response->json('data');
        $this->assertEquals('Cheap In Stock Phone', $items[0]['name']);
    }

    public function test_search_products_with_all_filters_no_results()
    {
        $category = Category::factory()->create(['name' => 'Electronics']);

        Product::factory()->create([
            'name' => 'Expensive Out of Stock Phone',
            'price' => 500.00,
            'in_stock' => false,
            'category_id' => $category->id,
        ]);

        $response = $this->postJson($this->route, [
            'query' => 'Phone',
            'category' => $category->id,
            'price_from' => 100,
            'price_to' => 200,
            'in_stock' => true,
            'per_page' => 10,
        ]);

        $response->assertOk();
        $this->assertEquals(0, $response->json('paginator')['total']);
        $this->assertCount(0, $response->json('data'));
    }
}

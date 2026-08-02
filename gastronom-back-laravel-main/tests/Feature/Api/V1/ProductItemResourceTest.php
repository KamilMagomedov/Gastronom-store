<?php

namespace Tests\Feature\Api\V1;

use App\Http\Resources\Api\V1\ProductItemResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductItemResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_item_resource_returns_single_image(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()
            ->withImages(2)
            ->create(['category_id' => $category->id]);

        $resource = ProductItemResource::make($product);
        $data = $resource->toArray(request());

        // Check that image field exists and is a string
        $this->assertArrayHasKey('image', $data);
        $this->assertIsString($data['image']);
        $this->assertNotEmpty($data['image']);

        // Check that other fields exist
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('old_price', $data);
        $this->assertArrayHasKey('sku', $data);
        $this->assertArrayHasKey('unit', $data);
        $this->assertArrayHasKey('category', $data);
    }

    public function test_product_item_resource_with_no_images_returns_empty_string(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()
            ->withoutImages()
            ->create(['category_id' => $category->id]);

        $resource = ProductItemResource::make($product);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('image', $data);
        $this->assertIsString($data['image']);
        $this->assertEmpty($data['image']);
    }
}

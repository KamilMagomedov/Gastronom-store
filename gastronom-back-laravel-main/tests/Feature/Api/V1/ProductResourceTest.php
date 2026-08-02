<?php

namespace Tests\Feature\Api\V1;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_resource_returns_images_array(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()
            ->withImages(3)
            ->create(['category_id' => $category->id]);

        $resource = ProductResource::make($product);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('images', $data);
        $this->assertIsArray($data['images']);
        $this->assertNotEmpty($data['images']);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('old_price', $data);
        $this->assertArrayHasKey('sku', $data);
        $this->assertArrayHasKey('unit', $data);
    }

    public function test_product_resource_with_no_images_returns_empty_array(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()
            ->withoutImages()
            ->create(['category_id' => $category->id]);

        $resource = ProductResource::make($product);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('images', $data);
        $this->assertIsArray($data['images']);
        $this->assertEmpty($data['images']);
    }
}

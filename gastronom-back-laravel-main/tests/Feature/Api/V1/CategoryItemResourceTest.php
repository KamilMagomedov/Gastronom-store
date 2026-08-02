<?php

namespace Tests\Feature\Api\V1;

use App\Http\Resources\Api\V1\CategoryItemResource;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryItemResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_item_resource_returns_image(): void
    {
        $category = Category::factory()
            ->withImages(1)
            ->create();

        $resource = CategoryItemResource::make($category);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('image', $data);
        $this->assertIsString($data['image']);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
    }

    public function test_category_item_resource_with_no_images_returns_empty_string(): void
    {
        $category = Category::factory()
            ->withoutImages()
            ->create();

        $resource = CategoryItemResource::make($category);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('image', $data);
        $this->assertIsString($data['image']);
        $this->assertEmpty($data['image']);
    }

    public function test_category_item_resource_structure(): void
    {
        $category = Category::factory()->create();

        $resource = CategoryItemResource::make($category);
        $data = $resource->toArray(request());

        $expectedKeys = ['id', 'name', 'slug', 'image'];
        $this->assertEqualsCanonicalizing($expectedKeys, array_keys($data));
    }
}

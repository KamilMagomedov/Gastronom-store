<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeCategoriesApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $route;

    protected Collection $activeCategories;

    protected Collection $inactiveCategories;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.home.categories');
    }

    public function test_home_categories_api_returns_success_response(): void
    {
        $this->seedDefaultData();

        $response = $this->getJson($this->route);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
                'success',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_home_categories_returns_only_active_categories(): void
    {
        $this->seedDefaultData();

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        foreach ($this->activeCategories as $category) {
            $this->assertContains($category->id, $categoryIds);
        }

        foreach ($this->inactiveCategories as $category) {
            $this->assertNotContains($category->id, $categoryIds);
        }
    }

    public function test_home_categories_orders_by_name(): void
    {
        $categoryC = Category::factory()->create([
            'is_active' => true,
            'name' => 'C Category',
        ]);

        $categoryA = Category::factory()->create([
            'is_active' => true,
            'name' => 'A Category',
        ]);

        $categoryB = Category::factory()->create([
            'is_active' => true,
            'name' => 'B Category',
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertEquals($categoryA->id, $categoryIds[0]);
        $this->assertEquals($categoryB->id, $categoryIds[1]);
        $this->assertEquals($categoryC->id, $categoryIds[2]);
    }

    public function test_home_categories_returns_correct_data_structure(): void
    {
        $this->seedDefaultData();

        $response = $this->getJson($this->route);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (! empty($data)) {
            $firstCategory = $data[0];
            $this->assertArrayHasKey('id', $firstCategory);
            $this->assertArrayHasKey('name', $firstCategory);
            $this->assertArrayHasKey('slug', $firstCategory);
        }
    }

    public function test_home_categories_handles_empty_database(): void
    {
        $response = $this->getJson($this->route);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'success' => true,
            ]);
    }

    public function test_home_categories_returns_all_active_ordered_by_name(): void
    {
        $categoryA = Category::factory()->create([
            'is_active' => true,
            'name' => 'A Category',
        ]);

        $categoryB = Category::factory()->create([
            'is_active' => true,
            'name' => 'B Category',
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertEquals($categoryA->id, $categoryIds[0]);
        $this->assertEquals($categoryB->id, $categoryIds[1]);
    }

    public function test_home_categories_api_response_time(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson($this->route);

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);

        $this->assertLessThan(1.0, $responseTime, 'API response time should be under 1 second');
    }

    public function test_home_categories_includes_description_when_exists(): void
    {
        $categoryWithDescription = Category::factory()->create([
            'is_active' => true,
            'description' => 'Test category description',
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($categoryWithDescription->id, $categoryIds);
    }

    public function test_home_categories_returns_correct_content_type(): void
    {
        $response = $this->getJson($this->route);

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/json');
    }

    public function test_home_categories_with_mixed_active_inactive(): void
    {
        $active1 = Category::factory()->create(['is_active' => true, 'name' => 'Active A']);
        $inactive1 = Category::factory()->create(['is_active' => false, 'name' => 'Inactive B']);
        $active2 = Category::factory()->create(['is_active' => true, 'name' => 'Active C']);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryNames = collect($data)->pluck('name')->toArray();

        // Should only contain active categories
        $this->assertContains('Active A', $categoryNames);
        $this->assertContains('Active C', $categoryNames);
        $this->assertNotContains('Inactive B', $categoryNames);

        $this->assertEquals('Active A', $categoryNames[0]);
        $this->assertEquals('Active C', $categoryNames[1]);
    }

    public function test_home_categories_with_special_characters(): void
    {
        $categoryWithSpecialChars = Category::factory()->create([
            'is_active' => true,
            'name' => 'Electronics & Technology',
            'slug' => 'electronics-and-technology',
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($categoryWithSpecialChars->id, $categoryIds);

        $foundCategory = collect($data)->firstWhere('id', $categoryWithSpecialChars->id);
        $this->assertEquals('Electronics & Technology', $foundCategory['name']);
        $this->assertEquals('electronics-and-technology', $foundCategory['slug']);
    }

    public function test_home_categories_with_duplicate_names(): void
    {
        $category1 = Category::factory()->create([
            'is_active' => true,
            'name' => 'Electronics',
        ]);

        $category2 = Category::factory()->create([
            'is_active' => true,
            'name' => 'Electronics Pro',
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($category1->id, $categoryIds);
        $this->assertContains($category2->id, $categoryIds);

        $foundCategory1 = collect($data)->firstWhere('id', $category1->id);
        $foundCategory2 = collect($data)->firstWhere('id', $category2->id);
        $this->assertEquals('Electronics', $foundCategory1['name']);
        $this->assertEquals('Electronics Pro', $foundCategory2['name']);
    }

    public function test_home_categories_with_long_names(): void
    {
        $longName = 'This is a very long category name that might cause issues in some systems but should work fine in our Laravel application';

        $categoryWithLongName = Category::factory()->create([
            'is_active' => true,
            'name' => $longName,
        ]);

        $response = $this->getJson($this->route);

        $data = $response->json('data');
        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($categoryWithLongName->id, $categoryIds);

        $foundCategory = collect($data)->firstWhere('id', $categoryWithLongName->id);
        $this->assertEquals($longName, $foundCategory['name']);
    }

    public function test_response_excluded_first_system_category(): void
    {
        $this->seedDefaultData();

        $systemCategory = Category::where('is_system', true)->firstOrFail();

        $response = $this->getJson($this->route);

        $data = $response->json('data');

        $categoryIds = collect($data)->pluck('id')->toArray();

        $this->assertNotContains($systemCategory->id, $categoryIds);
    }

    protected function seedDefaultData(): void
    {
        Category::firstOrCreate(
            ['slug' => 'uncategorized'],
            [
                'name' => 'Uncategorized',
                'description' => 'Products without category',
                'is_system' => true,
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        $this->activeCategories = Category::factory(5)->create(['is_active' => true]);
        $this->inactiveCategories = Category::factory(3)->create(['is_active' => false]);
    }
}

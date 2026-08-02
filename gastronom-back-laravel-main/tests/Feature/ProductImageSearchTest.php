<?php

namespace Tests\Feature;

use App\Jobs\SearchProductImageJob;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProductImageSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_jobs_for_product_range(): void
    {
        // Создаем тестовые товары
        $products = Product::factory()->count(3)->create([
            'name' => 'Test Product',
        ]);

        $fromId = $products->first()->id;
        $toId = $products->last()->id;

        // Фейкаем Bus чтобы отследить диспетчеризацию
        Bus::fake();

        // Выполняем команду
        $this->artisan('products:search-images', [
            'from_id' => $fromId,
            'to_id' => $toId,
        ])
            ->assertExitCode(0)
            ->expectsOutput("Searching images for products from ID {$fromId} to {$toId}")
            ->expectsOutput('Successfully dispatched 3 jobs for image processing');

        // Проверяем что Job были диспетчеризированы
        Bus::assertDispatchedTimes(SearchProductImageJob::class, 3);

        foreach ($products as $product) {
            Bus::assertDispatched(SearchProductImageJob::class, function ($job) use ($product) {
                return $job->productId === $product->id &&
                       $job->productName === $product->name;
            });
        }
    }

    public function test_command_handles_empty_range(): void
    {
        Bus::fake();

        $this->artisan('products:search-images', [
            'from_id' => 999,
            'to_id' => 1000,
        ])
            ->assertExitCode(0)
            ->expectsOutput('No products found in the specified range');

        Bus::assertNotDispatched(SearchProductImageJob::class);
    }

    public function test_command_validates_id_range(): void
    {
        $this->artisan('products:search-images', [
            'from_id' => 100,
            'to_id' => 50,
        ])
            ->assertExitCode(1)
            ->expectsOutput('from_id must be less than or equal to to_id');
    }
}

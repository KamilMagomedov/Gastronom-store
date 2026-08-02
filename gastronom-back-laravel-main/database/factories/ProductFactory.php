<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $price = fake()->randomFloat(2, 10, 500);
        $hasDiscount = fake()->boolean(30); // 30% chance of having discount

        return [
            'name' => ucfirst($name),
            'slug' => str()->slug($name),
            'description' => fake()->paragraphs(2, true),
            'price' => $price,
            'old_price' => $hasDiscount ? fake()->randomFloat(2, $price + 10, $price + 100) : null,
            'sku' => fake()->unique()->bothify('PRD-####-??'),
            'external_id' => fake()->optional(0.7)->numerify('EXT-########'),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'in_stock' => true,
            'is_active' => true,
            'weight' => fake()->randomFloat(3, 0.1, 10),
            'unit' => fake()->randomElement(['шт', 'кг', 'л', 'м', 'г', 'мл']),
            'category_id' => \App\Models\Category::factory(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function withImages(int $count = 1): static
    {
        $fixturePath = __DIR__.'/../../tests/fixtures/test-image.jpg';

        return $this->afterCreating(function (Product $product) use ($count, $fixturePath) {
            for ($i = 0; $i < $count; $i++) {
                $product->addMedia($fixturePath)
                    ->preservingOriginal()
                    ->toMediaCollection('images');
            }
        });
    }

    public function withoutImages(): static
    {
        return $this;
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'in_stock' => false,
        ]);
    }

    public function withDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_price' => fake()->randomFloat(2, $attributes['price'] + 10, $attributes['price'] + 100),
        ]);
    }
}

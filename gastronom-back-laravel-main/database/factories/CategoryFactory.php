<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str()->slug($name),
            'description' => fake()->optional(0.7)->paragraph(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->paragraphs(2, true),
        ]);
    }

    public function withImages(int $count = 1): static
    {
        $fixturePath = __DIR__.'/../../tests/fixtures/test-image.jpg';

        return $this->afterCreating(function (\App\Models\Category $category) use ($count, $fixturePath) {
            for ($i = 0; $i < $count; $i++) {
                $category->addMedia($fixturePath)
                    ->preservingOriginal()
                    ->toMediaCollection('icon');
            }
        });
    }

    public function withoutImages(): static
    {
        return $this;
    }
}

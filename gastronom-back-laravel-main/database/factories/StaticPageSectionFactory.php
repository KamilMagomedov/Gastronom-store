<?php

namespace Database\Factories;

use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaticPageSection>
 */
class StaticPageSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'static_page_id' => StaticPage::factory(),
            'number' => fake()->numberBetween(1, 10),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'important_note' => fake()->optional()->sentence(),
        ];
    }
}

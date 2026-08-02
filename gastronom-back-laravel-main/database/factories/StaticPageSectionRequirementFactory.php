<?php

namespace Database\Factories;

use App\Models\StaticPageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaticPageSectionRequirement>
 */
class StaticPageSectionRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'static_page_section_id' => StaticPageSection::factory(),
            'requirement' => fake()->sentence(),
        ];
    }
}

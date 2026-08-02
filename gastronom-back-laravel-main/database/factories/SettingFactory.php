<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->randomElement([
                $this->faker->word(),
                $this->faker->sentence(),
                $this->faker->randomNumber(),
                $this->faker->boolean(),
                ['key' => $this->faker->word(), 'value' => $this->faker->url()],
            ]),
            'type' => $this->faker->randomElement(['text', 'number', 'boolean', 'json']),
            'group' => $this->faker->randomElement(['general', 'delivery', 'social', 'app']),
            'description' => $this->faker->sentence(),
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Acquirer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'gateway' => null,
            'acquirer_id' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Банковская карта',
            'description' => 'Оплата банковской картой онлайн',
            'gateway' => 'sberbank',
            'acquirer_id' => Acquirer::factory()->sberbank(),
        ]);
    }

    public function sbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'СБП (Система быстрых платежей)',
            'description' => 'Оплата через СБП — любой банк России',
            'gateway' => 'tinkoff_sbp',
            'acquirer_id' => Acquirer::factory()->tinkoff(),
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Наличные',
            'description' => 'Оплата наличными при получении',
            'gateway' => null,
            'acquirer_id' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Acquirer>
 */
class AcquirerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'config' => [
                'merchant_id' => fake()->uuid(),
                'api_key' => fake()->uuid(),
                'secret_key' => fake()->uuid(),
            ],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function sberbank(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Сбербанк',
            'code' => 'sberbank',
            'config' => [
                'merchant_id' => 'sber_test_merchant',
                'login' => 'sber_test_login',
                'password' => 'sber_test_password',
            ],
        ]);
    }

    public function tinkoff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Тинькофф',
            'code' => 'tinkoff',
            'config' => [
                'terminal_key' => 'tink_test_terminal',
                'secret_key' => 'tink_test_secret',
                'terminal_password' => 'tink_test_password',
            ],
        ]);
    }

    public function vtb(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'ВТБ',
            'code' => 'vtb',
            'config' => [
                'merchant_id' => 'vtb_test_merchant',
                'api_key' => 'vtb_test_api_key',
            ],
        ]);
    }

    public function alfa(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Альфа-Банк',
            'code' => 'alfa',
            'config' => [
                'merchant_id' => 'alfa_test_merchant',
                'api_key' => 'alfa_test_api_key',
            ],
        ]);
    }

    public function psb(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'ПСБ',
            'code' => 'psb',
            'config' => [
                'merchant_id' => 'psb_test_merchant',
                'secret_key' => 'psb_test_secret',
            ],
        ]);
    }

    public function gazprom(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Газпромбанк',
            'code' => 'gazprom',
            'config' => [
                'merchant_id' => 'gaz_test_merchant',
                'api_key' => 'gaz_test_api_key',
            ],
        ]);
    }
}

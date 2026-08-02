<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncLog>
 */
class SyncLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => $this->faker->randomElement(['1C', 'API']),
            'entity_type' => $this->faker->randomElement(['products', 'orders', 'customers', 'categories']),
            'entity_id' => $this->faker->randomNumber(),
            'operation' => $this->faker->randomElement(['create', 'update', 'delete', 'sync']),
            'status' => $this->faker->randomElement(['success', 'error']),
            'message' => $this->faker->randomElement([
                'Синхронизация прошла успешно',
                'Ошибка подключения к 1С',
                'Неверный формат данных',
                'Товар успешно обновлен',
                'Заказ не найден в 1С',
                'Клиент создан успешно',
            ]),
            'data' => $this->faker->randomElement([
                null,
                ['id' => $this->faker->randomNumber(), 'name' => $this->faker->word],
                ['error' => $this->faker->sentence()],
            ]),
            'synced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}

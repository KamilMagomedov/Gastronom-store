<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'currency' => 'RUB',
            'payment_method' => $this->faker->randomElement(['mobile_app', 'card', 'cash']),
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway' => $this->faker->randomElement(['stripe', 'tinkoff', 'sberbank']),
            'status' => $this->faker->randomElement(TransactionStatus::cases()),
            'gateway_response' => [
                'success' => $this->faker->boolean(80),
                'message' => $this->faker->sentence(),
                'code' => $this->faker->randomNumber(3),
            ],
            'notes' => $this->faker->optional()->sentence(),
            'processed_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::COMPLETED,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::FAILED,
            'processed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::PENDING,
            'processed_at' => null,
        ]);
    }
}

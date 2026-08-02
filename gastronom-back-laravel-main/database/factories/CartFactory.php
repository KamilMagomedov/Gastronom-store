<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'session_id' => Str::uuid()->toString(),
            'total_amount' => fake()->randomFloat(2, 0, 1000),
            'total_items' => fake()->numberBetween(0, 10),
            'expires_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ];
    }

    public function forCustomer(Customer|int $customer): static
    {
        return $this->state(function (array $attributes) use ($customer) {
            return [
                'customer_id' => is_object($customer) ? $customer->id : $customer,
                'session_id' => null,
            ];
        });
    }
}

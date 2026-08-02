<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'total_amount' => $this->faker->randomFloat(2, 100, 1000),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'shipping_amount' => $this->faker->randomFloat(2, 0, 100),
            'delivery_method_id' => DeliveryMethod::query()->inRandomOrder()?->first()?->id ?? DeliveryMethod::factory(),
            'delivery_cost' => $this->faker->randomFloat(2, 0, 300),
            'delivery_phone' => $this->faker->phoneNumber(),
            'delivery_notes' => $this->faker->optional()->sentence(),
            'delivery_street' => $this->faker->streetName(),
            'delivery_city' => $this->faker->city(),
            'delivery_apartment' => $this->faker->optional()->numberBetween(1, 200),
            'delivery_postal_code' => $this->faker->optional()->postcode(),
            'delivery_latitude' => $this->faker->optional()->latitude(-90, 90),
            'delivery_longitude' => $this->faker->optional()->longitude(-180, 180),
            'delivery_building' => $this->faker->optional()->numberBetween(1, 50),
            'delivery_entrance' => $this->faker->optional()->numberBetween(1, 20),
            'payment_method_id' => PaymentMethod::query()->inRandomOrder()?->first()?->id ?? PaymentMethod::factory(),
            'status' => $this->faker->randomElement(OrderStatus::cases())->value,
            'payment_status' => $this->faker->randomElement(PaymentStatus::cases())->value,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::get()
            ->each(fn ($customer) => Order::factory(5)
                ->has(OrderItem::factory()->count(5))
                ->for($customer)->create());
    }
}

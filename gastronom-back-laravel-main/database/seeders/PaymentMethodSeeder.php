<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Наличные при получении',
                'description' => 'Оплата наличными курьеру при доставке',
                'is_active' => true,
                'sort_order' => 1,
            ],
            //            [
            //                'name' => 'Apple Pay',
            //                'description' => 'Быстрая оплата через Apple Pay',
            //                'is_active' => true,
            //                'sort_order' => 2,
            //            ],
            //            [
            //                'name' => 'Google Pay',
            //                'description' => 'Быстрая оплата через Google Pay',
            //                'is_active' => true,
            //                'sort_order' => 3,
            //            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }
    }
}

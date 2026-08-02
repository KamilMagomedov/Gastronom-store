<?php

namespace Database\Seeders;

use App\Models\DeliveryMethod;
use Illuminate\Database\Seeder;

class DeliveryMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveryMethods = [
            [
                'name' => 'Самовывоз',
                'description' => 'Заберите заказ самостоятельно в нашем пункте выдачи',
                'cost' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Курьерская доставка',
                'description' => 'Доставка курьером по указанному адресу',
                'cost' => 300,
                'is_active' => true,
                'sort_order' => 2,
            ],
            //            [
            //                'name' => 'Служба доставки',
            //                'description' => 'Доставка через службу доставки (СДЭК, Почта России и т.д.)',
            //                'cost' => 250,
            //                'is_active' => true,
            //                'sort_order' => 3,
            //            ],
        ];

        foreach ($deliveryMethods as $method) {
            DeliveryMethod::create($method);
        }
    }
}

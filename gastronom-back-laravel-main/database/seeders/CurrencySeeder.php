<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'Российский рубль',
                'code' => 'RUB',
                'symbol' => '₽',
                'exchange_rate' => 1.000000,
                'is_active' => true,
            ],
            //            [
            //                'name' => 'Доллар США',
            //                'code' => 'USD',
            //                'symbol' => '$',
            //                'exchange_rate' => 90.000000,
            //                'is_active' => false,
            //            ],
            //            [
            //                'name' => 'Евро',
            //                'code' => 'EUR',
            //                'symbol' => '€',
            //                'exchange_rate' => 98.000000,
            //                'is_active' => false,
            //            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}

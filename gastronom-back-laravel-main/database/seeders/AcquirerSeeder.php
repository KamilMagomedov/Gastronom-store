<?php

namespace Database\Seeders;

use App\Models\Acquirer;
use Illuminate\Database\Seeder;

class AcquirerSeeder extends Seeder
{
    public function run(): void
    {
        Acquirer::create([
            'name' => 'Сбербанк',
            'code' => 'sberbank',
            'description' => 'Эквайринг Сбербанка — оплата картами и Сбербанк Онлайн',
            'config' => [
                'merchant_id' => env('SBER_MERCHANT_ID'),
                'login' => env('SBER_LOGIN'),
                'password' => env('SBER_PASSWORD'),
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Acquirer::create([
            'name' => 'Тинькофф',
            'code' => 'tinkoff',
            'description' => 'Эквайринг Тинькофф — оплата картами и Tinkoff Pay',
            'config' => [
                'terminal_key' => env('TINKOFF_TERMINAL_KEY'),
                'secret_key' => env('TINKOFF_SECRET_KEY'),
                'terminal_password' => env('TINKOFF_TERMINAL_PASSWORD'),
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Acquirer::create([
            'name' => 'ВТБ',
            'code' => 'vtb',
            'description' => 'Эквайринг ВТБ — оплата картами и ВТБ Онлайн',
            'config' => [
                'merchant_id' => env('VTB_MERCHANT_ID'),
                'api_key' => env('VTB_API_KEY'),
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Acquirer::create([
            'name' => 'Альфа-Банк',
            'code' => 'alfa',
            'description' => 'Эквайринг Альфа-Банка — оплата картами и Альфа-Клик',
            'config' => [
                'merchant_id' => env('ALFA_MERCHANT_ID'),
                'api_key' => env('ALFA_API_KEY'),
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);

        Acquirer::create([
            'name' => 'ПСБ',
            'code' => 'psb',
            'description' => 'Эквайринг ПСБ — оплата картами и ПСБ Онлайн',
            'config' => [
                'merchant_id' => env('PSB_MERCHANT_ID'),
                'secret_key' => env('PSB_SECRET_KEY'),
            ],
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Acquirer::create([
            'name' => 'Газпромбанк',
            'code' => 'gazprom',
            'description' => 'Эквайринг Газпромбанка — оплата картами и Газпромбанк Онлайн',
            'config' => [
                'merchant_id' => env('GAZPROM_MERCHANT_ID'),
                'api_key' => env('GAZPROM_API_KEY'),
            ],
            'is_active' => true,
            'sort_order' => 6,
        ]);
    }
}

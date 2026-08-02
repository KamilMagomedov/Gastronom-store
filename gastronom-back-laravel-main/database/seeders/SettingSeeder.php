<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // App settings
            [
                'key' => 'app_name',
                'value' => 'Домашний гастроном',
                'type' => 'text',
                'group' => 'app',
                'description' => 'Application name',
                'is_public' => true,
            ],
            [
                'key' => 'app_version',
                'value' => '1.0.0',
                'type' => 'text',
                'group' => 'app',
                'description' => 'Application version',
                'is_public' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'RUB',
                'type' => 'text',
                'group' => 'app',
                'description' => 'Валюта Рубль',
                'is_public' => true,
            ],

            [
                'key' => 'contact_email',
                'value' => 'support@example.com',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Email для контактной информации',
                'is_public' => true,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+74951234567',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Контактный телефон',
                'is_public' => true,
            ],

            [
                'key' => 'social_links',
                'value' => json_encode([
                    'facebook' => 'https://facebook.com/example',
                    'instagram' => 'https://instagram.com/example',
                ]),
                'type' => 'json',
                'group' => 'social',
                'description' => 'Ссылки на социальные сети',
                'is_public' => true,
            ],

            [
                'key' => 'min_order_amount',
                'value' => '500.00',
                'type' => 'number',
                'group' => 'delivery',
                'description' => 'Минимальная сумма заказа',
                'is_public' => true,
            ],
            [
                'key' => 'free_delivery_threshold',
                'value' => '1500.00',
                'type' => 'number',
                'group' => 'delivery',
                'description' => 'Порог бесплатной доставки',
                'is_public' => true,
            ],
            [
                'key' => 'delivery_fee',
                'value' => '150.00',
                'type' => 'number',
                'group' => 'delivery',
                'description' => 'Стоимость доставки',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}

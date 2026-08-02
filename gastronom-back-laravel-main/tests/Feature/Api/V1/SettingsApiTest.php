<?php

namespace Tests\Feature\Api\V1;

use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_api_returns_correct_structure()
    {
        // Создаем тестовые настройки
        Setting::factory()->create([
            'key' => 'app_name',
            'value' => 'Test App',
            'type' => 'text',
            'group' => 'app',
            'is_public' => true,
        ]);

        Setting::factory()->create([
            'key' => 'currency',
            'value' => 'USD',
            'type' => 'text',
            'group' => 'app',
            'is_public' => true,
        ]);

        Setting::factory()->create([
            'key' => 'social_links',
            'value' => json_encode([
                'facebook' => 'https://facebook.com/test',
                'twitter' => 'https://twitter.com/test',
            ]),
            'type' => 'json',
            'group' => 'social',
            'is_public' => true,
        ]);

        Setting::factory()->create([
            'key' => 'min_order_amount',
            'value' => '100.50',
            'type' => 'number',
            'group' => 'delivery',
            'is_public' => true,
        ]);

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'app_name',
                    'app_version',
                    'currency',
                    'contact_email',
                    'contact_phone',
                    'social_links',
                    'delivery_settings' => [
                        'min_order_amount',
                        'free_delivery_threshold',
                        'delivery_fee',
                    ],
                    'delivery_methods',
                    'payment_methods',
                    'order_statuses',
                    'payment_statuses',
                ],
                'success',
            ]);

        $response->assertJson([
            'data' => [
                'app_name' => 'Test App',
                'currency' => 'USD',
                'social_links' => [
                    'facebook' => 'https://facebook.com/test',
                    'twitter' => 'https://twitter.com/test',
                ],
                'delivery_settings' => [
                    'min_order_amount' => '100.50',
                ],
            ],
        ]);

        $this->assertIsArray($response->json('data.social_links'));
    }

    public function test_settings_returns_payment_methods_with_gateway(): void
    {
        PaymentMethod::factory()->online()->create([
            'name' => 'Банковская карта',
        ]);

        PaymentMethod::factory()->sbp()->create([
            'name' => 'СБП',
        ]);

        PaymentMethod::factory()->cash()->create([
            'name' => 'Наличные',
            'gateway' => null,
        ]);

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200);

        $paymentMethods = $response->json('data.payment_methods');

        $this->assertCount(3, $paymentMethods);

        $onlineMethod = collect($paymentMethods)->firstWhere('name', 'Банковская карта');
        $this->assertNotNull($onlineMethod);

        $sbpMethod = collect($paymentMethods)->firstWhere('name', 'СБП');
        $this->assertNotNull($sbpMethod);

        $cashMethod = collect($paymentMethods)->firstWhere('name', 'Наличные');
        $this->assertNotNull($cashMethod);
    }

    public function test_settings_api_returns_default_values_when_no_settings_exist()
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'app_name' => 'Home Delivery App',
                    'currency' => 'RUB',
                    'social_links' => [],
                ],
            ]);
    }

    public function test_settings_api_only_returns_public_settings()
    {
        Setting::factory()->create([
            'key' => 'public_setting',
            'value' => 'public_value',
            'type' => 'text',
            'group' => 'general',
            'is_public' => true,
        ]);

        Setting::factory()->create([
            'key' => 'private_setting',
            'value' => 'private_value',
            'type' => 'text',
            'group' => 'general',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonMissing(['data' => ['private_setting' => 'private_value']]);
    }

    public function test_boolean_setting_conversion()
    {
        Setting::factory()->create([
            'key' => 'test_boolean',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'general',
            'is_public' => true,
        ]);

        $setting = Setting::where('key', 'test_boolean')->first();

        $this->assertTrue($setting->value);

        $setting->value = '0';
        $this->assertFalse($setting->value);
    }

    public function test_json_setting_conversion()
    {
        $jsonData = ['key' => 'value', 'number' => 123];

        Setting::factory()->create([
            'key' => 'test_json',
            'value' => json_encode($jsonData),
            'type' => 'json',
            'group' => 'general',
            'is_public' => true,
        ]);

        $setting = Setting::where('key', 'test_json')->first();

        $this->assertIsArray($setting->value);
        $this->assertEquals($jsonData, $setting->value);
    }
}

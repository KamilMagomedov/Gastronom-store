<?php

namespace Database\Seeders;

use App\Models\SyncLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SyncLogSeeder extends Seeder
{
    public function run(): void
    {
        $syncLogs = [
            [
                'source' => '1C',
                'entity_type' => 'products',
                'entity_id' => 123,
                'operation' => 'create',
                'status' => 'success',
                'message' => 'Товар успешно создан в 1С',
                'data' => [
                    'product_id' => 123,
                    'name' => 'Смартфон iPhone 15',
                    'sku' => 'IPHONE15-128GB',
                    'price' => 99999,
                    'stock_quantity' => 50,
                ],
                'synced_at' => Carbon::now()->subMinutes(5),
            ],
            [
                'source' => '1C',
                'entity_type' => 'products',
                'entity_id' => 124,
                'operation' => 'update',
                'status' => 'error',
                'message' => 'Ошибка обновления товара: неверный формат цены',
                'data' => [
                    'product_id' => 124,
                    'error' => 'Price format invalid: must be numeric',
                    'received_price' => '999.99.99',
                    'expected_format' => 'decimal(12,2)',
                ],
                'synced_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'source' => 'API',
                'entity_type' => 'orders',
                'entity_id' => 456,
                'operation' => 'sync',
                'status' => 'success',
                'message' => 'Заказ успешно синхронизирован с 1С',
                'data' => [
                    'order_id' => 456,
                    'customer_id' => 78,
                    'total_amount' => 2500,
                    'status' => 'confirmed',
                    'items_count' => 3,
                ],
                'synced_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'source' => '1C',
                'entity_type' => 'customers',
                'entity_id' => 78,
                'operation' => 'create',
                'status' => 'success',
                'message' => 'Новый клиент добавлен в 1С',
                'data' => [
                    'customer_id' => 78,
                    'name' => 'Иван Петров',
                    'email' => 'ivan.petrov@example.com',
                    'phone' => '+7 (999) 123-45-67',
                ],
                'synced_at' => Carbon::now()->subHour(1),
            ],
            [
                'source' => 'API',
                'entity_type' => 'categories',
                'entity_id' => 12,
                'operation' => 'update',
                'status' => 'error',
                'message' => 'Ошибка подключения к API 1С: timeout',
                'data' => [
                    'category_id' => 12,
                    'error' => 'Connection timeout after 30 seconds',
                    'endpoint' => 'https://1c-api.example.com/categories/12',
                    'attempt' => 3,
                ],
                'synced_at' => Carbon::now()->subHours(2),
            ],
            [
                'source' => '1C',
                'entity_type' => 'orders',
                'entity_id' => 457,
                'operation' => 'delete',
                'status' => 'success',
                'message' => 'Заказ удален из 1С по запросу клиента',
                'data' => [
                    'order_id' => 457,
                    'reason' => 'Customer request',
                    'deleted_by' => 'admin',
                    'backup_created' => true,
                ],
                'synced_at' => Carbon::now()->subHours(3),
            ],
            [
                'source' => 'API',
                'entity_type' => 'products',
                'entity_id' => 125,
                'operation' => 'sync',
                'status' => 'error',
                'message' => 'Товар не найден в 1С',
                'data' => [
                    'product_id' => 125,
                    'sku' => 'NONEXISTENT-001',
                    'error' => 'Product not found in 1C database',
                    '1c_response' => 'Product with SKU NONEXISTENT-001 does not exist',
                ],
                'synced_at' => Carbon::now()->subHours(4),
            ],
            [
                'source' => '1C',
                'entity_type' => 'customers',
                'entity_id' => 79,
                'operation' => 'update',
                'status' => 'success',
                'message' => 'Данные клиента обновлены в 1С',
                'data' => [
                    'customer_id' => 79,
                    'updated_fields' => ['phone', 'address'],
                    'old_phone' => '+7 (999) 111-22-33',
                    'new_phone' => '+7 (999) 999-88-77',
                ],
                'synced_at' => Carbon::now()->subHours(5),
            ],
            [
                'source' => 'API',
                'entity_type' => 'orders',
                'entity_id' => 458,
                'operation' => 'create',
                'status' => 'success',
                'message' => 'Новый заказ передан в 1С',
                'data' => [
                    'order_id' => 458,
                    'customer_id' => 80,
                    'total_amount' => 3450,
                    'payment_method' => 'card',
                    'delivery_method' => 'courier',
                ],
                'synced_at' => Carbon::now()->subHours(6),
            ],
            [
                'source' => '1C',
                'entity_type' => 'products',
                'entity_id' => 126,
                'operation' => 'sync',
                'status' => 'error',
                'message' => 'Ошибка валидации данных товара: отрицательный остаток',
                'data' => [
                    'product_id' => 126,
                    'error' => 'Stock quantity cannot be negative',
                    'received_quantity' => -5,
                    'validation_rules' => [
                        'stock_quantity' => 'required|integer|min:0',
                    ],
                ],
                'synced_at' => Carbon::now()->subHours(8),
            ],
        ];

        foreach ($syncLogs as $log) {
            SyncLog::create($log);
        }

        $this->command->info('Created 10 sync log entries with various scenarios');
    }
}

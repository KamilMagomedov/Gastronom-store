<?php

namespace Database\Seeders;

use App\Enums\ProductAttributeType;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Вес (в граммах)
        $weight = ProductAttribute::create([
            'name' => 'Вес',
            'type' => ProductAttributeType::NUMBER->value,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Цвет
        $color = ProductAttribute::create([
            'name' => 'Цвет',
            'type' => ProductAttributeType::SELECT->value,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Материал
        $material = ProductAttribute::create([
            'name' => 'Материал',
            'type' => ProductAttributeType::SELECT->value,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Размер
        $size = ProductAttribute::create([
            'name' => 'Размер',
            'type' => ProductAttributeType::SELECT->value,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => 4,
            'is_active' => true,
        ]);

        // Создаем опции для цвета
        $colorOptions = [
            ['key' => 'black', 'value' => 'Черный', 'sort_order' => 1],
            ['key' => 'white', 'value' => 'Белый', 'sort_order' => 2],
            ['key' => 'red', 'value' => 'Красный', 'sort_order' => 3],
            ['key' => 'blue', 'value' => 'Синий', 'sort_order' => 4],
            ['key' => 'green', 'value' => 'Зеленый', 'sort_order' => 5],
            ['key' => 'yellow', 'value' => 'Желтый', 'sort_order' => 6],
            ['key' => 'gray', 'value' => 'Серый', 'sort_order' => 7],
            ['key' => 'brown', 'value' => 'Коричневый', 'sort_order' => 8],
            ['key' => 'pink', 'value' => 'Розовый', 'sort_order' => 9],
            ['key' => 'purple', 'value' => 'Фиолетовый', 'sort_order' => 10],
            ['key' => 'orange', 'value' => 'Оранжевый', 'sort_order' => 11],
            ['key' => 'beige', 'value' => 'Бежевый', 'sort_order' => 12],
        ];

        foreach ($colorOptions as $option) {
            ProductAttributeOption::create([
                'product_attribute_id' => $color->id,
                'key' => $option['key'],
                'value' => $option['value'],
                'sort_order' => $option['sort_order'],
                'is_active' => true,
            ]);
        }

        // Создаем опции для материала
        $materialOptions = [
            ['key' => 'plastic', 'value' => 'Пластик', 'sort_order' => 1],
            ['key' => 'metal', 'value' => 'Металл', 'sort_order' => 2],
            ['key' => 'wood', 'value' => 'Дерево', 'sort_order' => 3],
            ['key' => 'glass', 'value' => 'Стекло', 'sort_order' => 4],
            ['key' => 'ceramic', 'value' => 'Керамика', 'sort_order' => 5],
            ['key' => 'fabric', 'value' => 'Ткань', 'sort_order' => 6],
            ['key' => 'leather', 'value' => 'Кожа', 'sort_order' => 7],
            ['key' => 'rubber', 'value' => 'Резина', 'sort_order' => 8],
            ['key' => 'paper', 'value' => 'Бумага', 'sort_order' => 9],
            ['key' => 'cardboard', 'value' => 'Картон', 'sort_order' => 10],
            ['key' => 'foam', 'value' => 'Пенопласт', 'sort_order' => 11],
            ['key' => 'silicone', 'value' => 'Силикон', 'sort_order' => 12],
            ['key' => 'bamboo', 'value' => 'Бамбук', 'sort_order' => 13],
            ['key' => 'stone', 'value' => 'Камень', 'sort_order' => 14],
            ['key' => 'concrete', 'value' => 'Бетон', 'sort_order' => 15],
        ];

        foreach ($materialOptions as $option) {
            ProductAttributeOption::create([
                'product_attribute_id' => $material->id,
                'key' => $option['key'],
                'value' => $option['value'],
                'sort_order' => $option['sort_order'],
                'is_active' => true,
            ]);
        }

        // Создаем опции для размера
        $sizeOptions = [
            ['key' => 'xs', 'value' => 'XS', 'sort_order' => 1],
            ['key' => 's', 'value' => 'S', 'sort_order' => 2],
            ['key' => 'm', 'value' => 'M', 'sort_order' => 3],
            ['key' => 'l', 'value' => 'L', 'sort_order' => 4],
            ['key' => 'xl', 'value' => 'XL', 'sort_order' => 5],
            ['key' => 'xxl', 'value' => 'XXL', 'sort_order' => 6],
            ['key' => 'xxxl', 'value' => 'XXXL', 'sort_order' => 7],
            ['key' => 'one_size', 'value' => 'One Size', 'sort_order' => 8],
        ];

        foreach ($sizeOptions as $option) {
            ProductAttributeOption::create([
                'product_attribute_id' => $size->id,
                'key' => $option['key'],
                'value' => $option['value'],
                'sort_order' => $option['sort_order'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Product attributes seeded successfully!');
        $this->command->info('Created attributes: Вес, Цвет, Материал, Размер');
        $this->command->info('Color options: '.count($colorOptions));
        $this->command->info('Material options: '.count($materialOptions));
        $this->command->info('Size options: '.count($sizeOptions));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::firstOrCreate(
            ['slug' => 'bez-kategorii'],
            [
                'name' => 'Без категории',
                'description' => 'Товары без категории',
                'is_system' => true,
                'sort_order' => 0,
                'is_active' => true,
            ]
        );
    }
}

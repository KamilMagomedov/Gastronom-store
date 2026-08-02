<?php

namespace Database\Seeders;

use App\Models\ProductSale;
use Illuminate\Database\Seeder;

class ProductSaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductSale::factory()->count(10)->create();
    }
}

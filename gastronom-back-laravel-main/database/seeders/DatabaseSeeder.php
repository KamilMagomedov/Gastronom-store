<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(StaticPageSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductAttributeSeeder::class);
        $this->call(ProductSaleSeeder::class);
        $this->call(ProductReviewSeeder::class);
        $this->call(DeliveryMethodSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(TransactionSeeder::class);
        $this->call(SyncLogSeeder::class);
        $this->call(CurrencySeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $customers = Customer::query()->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('No products or customers found. Please run other seeders first.');

            return;
        }

        // Create 3-8 reviews for each product
        $products->each(function (Product $product) use ($customers) {
            $reviewCount = rand(3, 8);
            $selectedCustomers = $customers->random(min($reviewCount, $customers->count()));

            $selectedCustomers->each(function (Customer $customer) use ($product) {
                ProductReview::factory()->create([
                    'product_id' => $product->id,
                    'customer_id' => $customer->id,
                    'rating' => rand(1, 5),
                    'comment' => fake()->text(),
                ]);
            });
        });

        $totalReviews = ProductReview::count();
        $this->command->info("Created {$totalReviews} product reviews.");
    }
}

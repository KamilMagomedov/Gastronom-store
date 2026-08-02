<?php

namespace App\Console\Commands;

use App\Jobs\SearchProductImageJob;
use App\Models\Product;
use Illuminate\Console\Command;

class ProcessProductImagesCommand extends Command
{
    protected $signature = 'products:search-images {from_id} {to_id?}';

    protected $description = 'Search and download images for products in ID range';

    public function handle()
    {
        $fromId = (int) $this->argument('from_id');
        $toId = (int) $this->argument('to_id');

        if ($fromId && $toId) {
            if ($fromId > $toId) {
                $this->error('from_id must be less than or equal to to_id');

                return 1;
            }
        }

        $this->info("Searching images for products from ID {$fromId} to {$toId}");

        if (empty($toId)) {
            $products = Product::query()->where('id', $fromId)->get();
        } else {
            $products = Product::whereBetween('id', [$fromId, $toId])
                ->select(['id', 'name'])
                ->get();
        }

        if ($products->isEmpty()) {
            $this->warn('No products found in the specified range');

            return 0;
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            SearchProductImageJob::dispatch($product->id, $product->name);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully dispatched {$products->count()} jobs for image processing");

        return 0;
    }
}

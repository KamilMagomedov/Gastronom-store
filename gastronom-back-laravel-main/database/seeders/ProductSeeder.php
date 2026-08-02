<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->count(5)
            // ->withImages()
            ->create();

        Media::query()->whereNull('uuid')
            ->each(function (Media $media) {
                $media->uuid = (string) Str::uuid();
                $media->save();
            });
    }
}

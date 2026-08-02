<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductAvailabilityResource;
use App\Models\Product;
use App\Support\ApiResponse;

class ProductAvailabilityController extends Controller
{
    public function __invoke(string $slug)
    {
        $product = Product::query()->where('slug', $slug)->first();

        if (! $product) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(
            ProductAvailabilityResource::make($product)->toArray(request())
        );
    }
}

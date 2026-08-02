<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Repositories\ProductRepository;
use App\Support\ApiResponse;

class ProductController extends Controller
{
    public function __invoke(ProductRepository $productRepository, string $slug)
    {
        $product = $productRepository->firstWhere('slug', $slug, with: ['media', 'category']);

        if (! $product) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(ProductResource::make($product)->toArray(request()));
    }
}

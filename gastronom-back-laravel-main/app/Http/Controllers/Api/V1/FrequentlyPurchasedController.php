<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductItemResource;
use App\Http\Resources\ApiResource;
use App\Repositories\ProductSaleRepository;
use App\Support\ApiResponse;

class FrequentlyPurchasedController extends Controller
{
    public function __invoke(ProductSaleRepository $productSaleRepository): ApiResource
    {
        return ApiResponse::success(
            ProductItemResource::collection(
                $productSaleRepository->frequentlyPurchased()
            )->toArray(request())
        );
    }
}

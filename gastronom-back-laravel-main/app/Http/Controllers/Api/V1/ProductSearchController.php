<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchProductsRequest;
use App\Http\Resources\Api\V1\ProductItemResource;
use App\Http\Resources\ApiResource;
use App\Strategies\SearchStrategyManager;
use App\Support\ApiResponse;

class ProductSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SearchStrategyManager $manager, SearchProductsRequest $request): ApiResource
    {
        $paginator = $manager
            ->getEloquentStrategy()
            ->search($request->getDto())
            ->through(fn ($product) => ProductItemResource::make($product));

        return ApiResponse::paginator($paginator);
    }
}

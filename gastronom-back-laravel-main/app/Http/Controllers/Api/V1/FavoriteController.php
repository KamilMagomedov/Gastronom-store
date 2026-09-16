<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductItemResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()
            ->favorites()
            ->with('category')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return ApiResponse::success(
            ProductItemResource::collection($products)
                ->toArray($request)
        );
    }

    public function store(
        Request $request,
        Product $product
    ) {
        $request->user()
            ->favorites()
            ->syncWithoutDetaching([
                $product->id,
            ]);

        return ApiResponse::success(
            ProductItemResource::make($product)
                ->toArray($request)
        );
    }

    public function destroy(
        Request $request,
        Product $product
    ) {
        $request->user()
            ->favorites()
            ->detach($product->id);

        return ApiResponse::success();
    }
}
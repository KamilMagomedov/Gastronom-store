<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryItemResource;
use App\Http\Resources\ApiResource;
use App\Repositories\CategoryRepository;
use App\Support\ApiResponse;

class HomeCategoriesController extends Controller
{
    public function __invoke(CategoryRepository $categoryRepository): ApiResource
    {
        $categories = $categoryRepository->getActiveForHome();

        return ApiResponse::success(
            CategoryItemResource::collection($categories)->toArray(request())
        );
    }
}

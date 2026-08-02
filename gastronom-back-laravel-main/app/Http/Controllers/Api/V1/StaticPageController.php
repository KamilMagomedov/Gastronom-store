<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StaticPageType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StaticPageResource;
use App\Http\Resources\ApiResource;
use App\Services\StaticPage\StaticPageService;
use App\Support\ApiResponse;

class StaticPageController extends Controller
{
    public function __construct(private StaticPageService $staticPageService) {}

    public function show(string $slug): ApiResource
    {
        if (! StaticPageType::tryFrom($slug)) {
            return ApiResponse::notFound();
        }

        $staticPage = $this->staticPageService->getPage($slug);

        return ApiResponse::success(StaticPageResource::make($staticPage)->toArray(request()));
    }
}

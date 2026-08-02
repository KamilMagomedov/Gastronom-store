<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SupportRequest;
use App\Http\Resources\ApiResource;
use App\Services\SupportService;
use App\Support\ApiResponse;

class SupportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SupportRequest $request, SupportService $service): ApiResource
    {
        $service->create(
            $request->validated('theme'),
            $request->validated('message'),
            $request->validated('order_id'),
        );

        return ApiResponse::success([
            'message' => 'Support request created successfully',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request): ApiResource
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success([
            'message' => 'Successfully logged out',
        ]);
    }
}

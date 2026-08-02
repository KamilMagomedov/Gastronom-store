<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidOrderStatusException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrderRepeatRequest;
use App\Http\Resources\Api\V1\OrderRepeatCheckResource;
use App\Http\Resources\Api\V1\SavedOrderResource;
use App\Services\OrderApiService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;

class OrderRepeatController extends Controller
{
    public function __construct(private OrderApiService $orderApiService) {}

    public function check(OrderRepeatRequest $request)
    {
        try {
            $checkResult = $this->orderApiService->checkOrderForRepeat($request->validated('order_id'));

            return ApiResponse::success(OrderRepeatCheckResource::make($checkResult)->toArray(request()));
        } catch (ModelNotFoundException) {
            return ApiResponse::notFound();
        } catch (UnauthorizedException $e) {
            return ApiResponse::laravelError(message: 'Access denied', statusCode: $e->getCode());
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), [
                'exception' => $e,
            ]);

            return ApiResponse::serverError();
        }
    }

    public function repeat(OrderRepeatRequest $request)
    {
        try {
            $order = $this->orderApiService->repeatOrder($request->validated('order_id'));

            return ApiResponse::success(SavedOrderResource::make($order)->toArray(request()));
        } catch (ModelNotFoundException) {
            return ApiResponse::notFound();
        } catch (InvalidOrderStatusException $e) {
            return ApiResponse::laravelError(message: $e->getMessage(), statusCode: $e->getCode());
        } catch (UnauthorizedException $e) {
            return ApiResponse::laravelError(message: 'Access denied', statusCode: $e->getCode());
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), [
                'exception' => $e,
            ]);

            return ApiResponse::serverError();
        }
    }
}

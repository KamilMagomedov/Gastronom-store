<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Cart\CartNotFoundException;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderStatusException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderCancelRequest;
use App\Http\Requests\Api\OrderRequest;
use App\Http\Requests\Api\OrderUpdateRequest;
use App\Http\Requests\Api\V1\OrderIndexRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\OrderShowResource;
use App\Http\Resources\Api\V1\SavedOrderResource;
use App\Models\Order;
use App\Services\OrderApiService;
use App\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;

class OrderController extends Controller
{
    public function __construct(
        private OrderApiService $orderApiService,
        private CartService $cartService 
    ) {}

    public function index(OrderIndexRequest $request)
    {
        $orders = $this->orderApiService->getOrders(
            $request->validated('per_page', 15),
            $request->validated('page', 1)
        );

        $modifiedOrders = $orders->through(fn (Order $order) => OrderResource::make($order));

        return ApiResponse::paginator($modifiedOrders);
    }

    public function show(int $id)
    {
        try {
            $order = $this->orderApiService->getOrderById($id);

            return ApiResponse::success(OrderShowResource::make($order)->toArray(request()));
        } catch (ModelNotFoundException) {
            return ApiResponse::notFound();
        } catch (UnauthorizedException $e) {
            return ApiResponse::laravelError(message: $e->getMessage(), statusCode: $e->getCode());
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return ApiResponse::serverError();
        }
    }

    public function store(OrderRequest $request)
    {
        try {
            $orderDto = $request->toDto();

            $order = $this->orderApiService->createOrder($orderDto);

            $sessionId = $request->input('session_id') 
                ?? $request->header('X-Session-Id') 
                ?? request()->input('session_id');

            if ($sessionId) {
                $this->cartService->clearCart($sessionId);
            }

            return ApiResponse::success(SavedOrderResource::make($order)->toArray(request()));
        } catch (CartNotFoundException|EmptyCartException $e) {
            return ApiResponse::laravelError($e->getMessage(), statusCode: Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (InsufficientStockException $e) {
            return ApiResponse::laravelError(
                message: $e->getMessage(),
                data: ['insufficient_items' => $e->getItems()],
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return ApiResponse::serverError();
        }
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        try {
            $order = $this->orderApiService->updateOrder($order, $request->toDto());

            return ApiResponse::success(SavedOrderResource::make($order)->toArray(request()));
        } catch (UnauthorizedException $e) {
            return ApiResponse::serverError(statusCode: $e->getCode());
        } catch (\Throwable) {
            return ApiResponse::serverError();
        }
    }

    public function cancel(OrderCancelRequest $request, Order $order)
    {
        try {
            $this->orderApiService->cancelOrder($order, $request->toDto());

            return ApiResponse::success();
        } catch (InvalidOrderStatusException $e) {
            return ApiResponse::serverError(statusCode: 400);
        } catch (UnauthorizedException $e) {
            return ApiResponse::laravelError(message: 'Access denied', statusCode: $e->getCode());
        } catch (\Throwable) {
            return ApiResponse::serverError();
        }
    }
}
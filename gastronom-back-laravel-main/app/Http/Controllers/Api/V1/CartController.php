<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Cart\CartNotFoundException;
use App\Exceptions\Cart\CartTokenRequiredException;
use App\Exceptions\Cart\NotEnoughStockException;
use App\Exceptions\Cart\ProductNotAvailableException;
use App\Exceptions\Cart\ProductNotFoundInCartException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddToCartRequest;
use App\Http\Requests\Api\V1\GetCartRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\ApiResource;
use App\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function index(GetCartRequest $request): ApiResource
    {
        $cart = $this->cartService->getCart($request->toDto());
        $cartData = CartResource::make($cart)->resolve();

        return ApiResponse::success($cartData);
    }

    public function store(AddToCartRequest $request): ApiResource
    {
        try {
            $cartDto = $request->toDto();
            
            $cart = $this->cartService->getOrCreateCart($cartDto->sessionId);
            
            $this->cartService->addToCart($cartDto);
            
            $cart->refresh();
            $cart->load(['items.product']);
            
            $cartData = CartResource::make($cart)->resolve();

            return ApiResponse::success($cartData);

        } catch (ProductNotAvailableException $e) {
            return ApiResponse::unprocessableEntity($e->getMessage());
        } catch (NotEnoughStockException $e) {
            return ApiResponse::unprocessableEntity($e->getMessage());
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return ApiResponse::serverError();
        }
    }

    public function destroy($id): ApiResource
    {
        try {
            $sessionId = request()->input('session_id') ?? request()->header('X-Session-Id');
            
            $this->cartService->removeProductFromCart($id, $sessionId);
            
            $cart = $this->cartService->getOrCreateCart($sessionId);
            
            $cart->load(['items.product']);
            
            $cartData = CartResource::make($cart)->resolve();

            return ApiResponse::success($cartData);

        } catch (ProductNotFoundInCartException|CartNotFoundException $e) {
            return ApiResponse::notFound(['message' => $e->getMessage()]);
        } catch (CartTokenRequiredException $e) {
            return ApiResponse::unprocessableEntity($e->getMessage());
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return ApiResponse::serverError();
        }
    }

    public function clear(GetCartRequest $request): ApiResource
    {
        try {
            $sessionId = $request->input('session_id') 
                ?? $request->header('X-Session-Id') 
                ?? request()->query('session_id');

            $this->cartService->clearCart($sessionId);

            return ApiResponse::success();
        } catch (CartNotFoundException $e) {
            return ApiResponse::notFound(['message' => $e->getMessage()]);
        } catch (CartTokenRequiredException $e) {
            return ApiResponse::unprocessableEntity($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Critical error in CartController@clear: ' . $e->getMessage());
            return ApiResponse::serverError();
        }
    }
}
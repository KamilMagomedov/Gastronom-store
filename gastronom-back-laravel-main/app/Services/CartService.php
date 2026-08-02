<?php

namespace App\Services;

use App\DTO\Cart\AddToCartData;
use App\DTO\Cart\GetCartData;
use App\Exceptions\Cart\CartNotFoundException;
use App\Exceptions\Cart\CartTokenRequiredException;
use App\Exceptions\Cart\NotEnoughStockException;
use App\Exceptions\Cart\ProductNotAvailableException;
use App\Exceptions\Cart\ProductNotFoundInCartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Repositories\CartItemRepository;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class CartService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository
    ) {}

    public function getCart(GetCartData $data): Cart
    {
        return $this->getOrCreateCart($data->sessionId);
    }

    public function addToCart(AddToCartData $data): CartItem
    {
        $product = $this->productRepository->find($data->productId);

        if (! $product) {
            throw new ModelNotFoundException;
        }

        if ($product->isNotAvailable()) {
            throw new ProductNotAvailableException;
        }

        if ($product->stock_quantity < $data->quantity) {
            throw new NotEnoughStockException;
        }

        return DB::transaction(function () use ($data, $product) {
            $cart = $this->getOrCreateCart($data->sessionId);
            $cartItem = $this->handleCartItem($cart, $product, $data->quantity);
            $this->updateCartTotals($cart);

            return $cartItem;
        });
    }

    public function removeProductFromCart(int $productId, ?string $sessionId): void
    {
        $cart = $this->getCartForContext($sessionId);

        $cartItem = $cart
            ->items()
            ->where('product_id', $productId)
            ->first();

        if (! $cartItem) {
            throw new ProductNotFoundInCartException;
        }

        DB::transaction(function () use ($cart, $cartItem) {
            $cartItem->delete();
            $this->updateCartTotals($cart);
        });
    }

    private function getCartForContext(?string $sessionId): Cart
    {
        if (Auth::guard('customers')->check()) {
            return $this->cartRepository->getCartForCustomer()
                ?? throw new CartNotFoundException;
        }

        if (! $sessionId) {
            throw new CartTokenRequiredException;
        }

        return $this->cartRepository->getCartForGuest($sessionId)
            ?? throw new CartNotFoundException;
    }

    public function clearCart(?string $sessionId = null): Cart
    {
        return $this->getOrCreateCart($sessionId)->reset();
    }

    private function handleCartItem(Cart $cart, Product $product, int $quantity): CartItem
    {
        $cartItem = $cart->findItem($product->id);

        if ($cartItem) {
            return $this->updateExistingCartItem($cartItem, $product, $quantity);
        }

        $initialQuantity = $quantity > 0 ? $quantity : 1;
        return $this->createNewCartItem($cart, $product, $initialQuantity);
    }

    private function updateExistingCartItem(CartItem $cartItem, Product $product, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $cartItem->delete();
            return $cartItem;
        }

        $cartItem->update([
            'quantity' => $quantity,
            'total' => $quantity * $cartItem->price,
        ]);

        return $cartItem;
    }

    private function createNewCartItem(Cart $cart, Product $product, int $quantity): CartItem
    {
        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
            'total' => $quantity * $product->price,
        ]);
    }

    public function getOrCreateCart(?string $sessionId): Cart
    {
        if (Auth::guard('customers')->check()) {
            return $this->cartRepository->getCartForCustomer() ?? $this->create([
                'customer_id' => Auth::guard('customers')->id(),
                'expires_at' => now()->addMinutes(config('cart.expires.customer')),
            ]);
        }

        $finalSessionId = (!empty($sessionId) && $sessionId !== 'undefined') ? $sessionId : (string) Str::uuid();

        return $this->cartRepository->getCartForGuest($finalSessionId) ?? $this->create([
            'session_id' => $finalSessionId,
            'expires_at' => now()->addMinutes(config('cart.expires.guest')),
        ]);
    }

    private function updateCartTotals(Cart $cart): void
    {
        $totals = $cart->items()->select([
            DB::raw('SUM(quantity) as total_items'),
            DB::raw('SUM(total) as total_amount'),
        ])->first();

        $cart->update([
            'total_items' => $totals->total_items ?? 0,
            'total_amount' => $totals->total_amount ?? 0,
        ]);
    }

    public function create(array $data): Cart
    {
        return Cart::query()->create($data);
    }
}
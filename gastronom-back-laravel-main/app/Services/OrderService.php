<?php

namespace App\Services;

use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PriceChangedException;
use App\Http\Resources\Api\V1\DeliveryMethodResource;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\Cart;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderFromCart(Cart $cart, CreateOrderData $data): Order
{
    $result = DB::transaction(function () use ($cart, $data) {
        $products = $this->lockProductsForCartItems($cart->items);

        $this->validateStock($cart->items, $products);

        $priceChangedItems = $this->refreshChangedCartPrices(
            $cart,
            $cart->items,
            $products
        );

        if (! empty($priceChangedItems)) {
            return [
                'order' => null,
                'price_changed_items' => $priceChangedItems,
            ];
        }

        $order = Order::create([
            'customer_id' => $cart->customer_id,
            'subtotal' => $cart->total_amount,
            'total_amount' => $this->calculateTotalAmount($cart, $data),
            'delivery_method_id' => $data->deliveryMethod,
            'delivery_cost' => $this->getDeliveryCost($data->deliveryMethod),
            'delivery_phone' => $data->deliveryPhone,
            'delivery_notes' => $data->deliveryNotes,
            'delivery_street' => $data->deliveryStreet,
            'delivery_city' => $data->deliveryCity,
            'delivery_apartment' => $data->deliveryApartment,
            'delivery_postal_code' => $data->deliveryPostalCode,
            'delivery_latitude' => $data->deliveryLatitude,
            'delivery_longitude' => $data->deliveryLongitude,
            'delivery_building' => $data->deliveryBuilding,
            'delivery_entrance' => $data->deliveryEntrance,
            'delivery_floor' => $data->deliveryFloor,
            'payment_method_id' => $data->paymentMethod,
            'payment_status' => PaymentStatus::PENDING->value,
            'status' => OrderStatus::PENDING->value,
            'notes' => $data->notes,
        ]);

        $this->createOrderItems($order, $cart->items);
        $order->recalculateTotals();
        $this->decrementStock($cart->items, $products);

        $cart->items()->delete();
        $cart->update([
            'total_items' => 0,
            'total_amount' => 0,
        ]);

        $order->load([
            'orderItems',
            'paymentMethod',
            'deliveryMethod',
        ]);

        $order->customer->notify(
            new OrderCreatedNotification($order)
        );

        return [
            'order' => $order,
            'price_changed_items' => [],
        ];
    });

    if (! empty($result['price_changed_items'])) {
        throw new PriceChangedException(
            'Цена товара изменилась',
            $result['price_changed_items']
        );
    }

    return $result['order'];
}

    public function updateOrderStatus(Order $order, OrderStatus $status): Order
    {
        $order->update(['status' => $status->value]);

        if ($status === OrderStatus::COMPLETED) {
            $order->update(['delivered_at' => now()]);
        }

        return $order;
    }

    public function updatePaymentStatus(Order $order, PaymentStatus $status): Order
    {
        $order->update(['payment_status' => $status->value]);

        return $order;
    }

    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            $order->update([
                'status' => OrderStatus::CANCELLED->value,
                'notes' => ($order->notes ? $order->notes."\n" : '').'Отменен: '.($reason ?? 'Причина не указана'),
            ]);

            foreach ($order->orderItems as $item) {
                $item?->product->increment('stock_quantity', $item->quantity);
            }

            return $order;
        });
    }

    public function getDeliveryOptions(): array
    {
        $deliveryMethods = DeliveryMethod::active()->get();

        return DeliveryMethodResource::collection($deliveryMethods)->toArray(request());
    }

    public function getPaymentOptions(): array
    {
        $paymentMethods = PaymentMethod::active()->get();

        return PaymentMethodResource::collection($paymentMethods)->toArray(request());
    }

    private function calculateTotalAmount(Cart $cart, CreateOrderData $data): float
    {
        $subtotal = $cart->total_amount;
        $deliveryCost = $this->getDeliveryCost($data->deliveryMethod);

        return $subtotal + $deliveryCost;
    }

    private function getDeliveryCost(?int $deliveryMethodId): float
    {
        if (! $deliveryMethodId) {
            return 0.00;
        }

        return DeliveryMethod::find($deliveryMethodId)?->cost ?? 0.00;
    }

    private function lockProductsForCartItems(Collection $cartItems): Collection
    {
        $productIds = $cartItems
            ->pluck('product_id')
            ->unique()
            ->sort()
            ->values();

        return Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function validateStock(
        Collection $cartItems,
        Collection $products
    ): void {
        $insufficient = [];

        foreach ($cartItems as $cartItem) {
            /** @var Product|null $product */
            $product = $products->get($cartItem->product_id);

            if (
                ! $product
                || $product->isNotEnoughStockAvailable($cartItem->quantity)
            ) {
                $insufficient[] = [
                    'product_id' => $cartItem->product_id,
                    'product_name' => $product?->name
                        ?? $cartItem->product?->name
                        ?? 'Unknown product',
                    'requested' => $cartItem->quantity,
                    'available' => $product?->stock_quantity ?? 0,
                ];
            }
        }

        if (! empty($insufficient)) {
            throw new InsufficientStockException(
                'Недостаточно товара на складе',
                $insufficient
            );
        }
    }

    private function refreshChangedCartPrices(
        Cart $cart,
        Collection $cartItems,
        Collection $products
    ): array {
        $changedItems = [];

        foreach ($cartItems as $cartItem) {
            /** @var Product|null $product */
            $product = $products->get($cartItem->product_id);

            if (! $product) {
                continue;
            }

            $oldPrice = number_format(
                (float) $cartItem->price,
                2,
                '.',
                ''
            );

            $newPrice = number_format(
                (float) $product->price,
                2,
                '.',
                ''
            );

            if ($oldPrice === $newPrice) {
                continue;
            }

            $newTotal = round(
                (float) $newPrice * $cartItem->quantity,
                2
            );

            $cartItem->update([
                'price' => $newPrice,
                'total' => $newTotal,
            ]);

            $changedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'quantity' => $cartItem->quantity,
            ];
        }

        if (! empty($changedItems)) {
            $cart->update([
                'total_items' => (int) $cart->items()->sum('quantity'),
                'total_amount' => (float) $cart->items()->sum('total'),
            ]);
        }

        return $changedItems;
    }

    private function decrementStock(
        Collection $cartItems,
        Collection $products
    ): void {
        foreach ($cartItems as $cartItem) {
            /** @var Product $product */
            $product = $products->get($cartItem->product_id);

            $product->decrement(
                'stock_quantity',
                $cartItem->quantity
            );
        }
    }

    private function createOrderItems(Order $order, Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name,
                'product_sku' => $cartItem->product->sku,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->price,
                'total_price' => $cartItem->total,
            ]);
        }
    }
}

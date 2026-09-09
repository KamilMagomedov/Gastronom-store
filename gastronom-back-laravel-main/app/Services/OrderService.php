<?php

namespace App\Services;

use App\DTO\Order\CreateOrderData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Resources\Api\V1\DeliveryMethodResource;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\Cart;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderFromCart(Cart $cart, CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            $this->validateStock($cart->items);

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
            $this->decrementStock($cart->items);

            $cart->items()->delete();
            $cart->update([
                'total_items' => 0,
                'total_amount' => 0,
            ]);

            $order->load(['orderItems', 'paymentMethod', 'deliveryMethod']);

            $order->customer->notify(new OrderCreatedNotification($order));

            return $order;
        });
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

    private function validateStock(Collection $cartItems): void
    {
        $insufficient = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            if ($product->isNotEnoughStockAvailable($cartItem->quantity)) {
                $insufficient[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'requested' => $cartItem->quantity,
                    'available' => $product->stock_quantity,
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

    private function decrementStock(Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
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

<?php

namespace App\Services;

use App\DTO\Order\CancelOrderData;
use App\DTO\Order\CreateOrderData;
use App\DTO\Order\DeliveryCalculateData;
use App\DTO\Order\UpdateOrderData;
use App\Exceptions\Cart\CartNotFoundException;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InvalidOrderStatusException;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Services\Payment\PaymentManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;

class OrderApiService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService,
        private CartRepository $cartRepository,
        private PaymentManager $paymentManager
    ) {}

    public function getOrders(int $perPage = 15, int $page = 1)
    {
        return $this->orderRepository->findByCustomer(
            auth('customers')->id(),
            $perPage,
            $page
        );
    }

    public function getOrderById(int $orderId): ?Order
    {
        $order = $this->orderRepository->findById($orderId, ['orderItems.product']);

        if (! $order) {
            throw new ModelNotFoundException;
        }

        if (! Gate::forUser(auth('customers')->user())->allows('order-owner', $order)) {
            throw new UnauthorizedException('Access denied', 403);
        }

        return $order;
    }

    public function createOrder(CreateOrderData $data)
    {
        $cart = $this->cartRepository->getCartForCustomer(['items.product']);

        if (! $cart) {
            throw new CartNotFoundException('Cart not found');
        }

        if ($cart->items->isEmpty()) {
            throw new EmptyCartException('Cannot create order from empty cart');
        }

        $order = $this->orderService->createOrderFromCart(
            $cart,
            $data
        );

        $order->loadMissing(['paymentMethod', 'orderItems', 'deliveryMethod', 'customer']);

        $paymentUrl = null;
        $paymentMethod = $order->paymentMethod;

        if ($paymentMethod && $this->paymentManager->isOnlinePayment($paymentMethod)) {
            try {
                $result = $this->paymentManager->initiateOnlinePayment($order);
                $paymentUrl = $result['payment_url'] ?? null;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-payment initiation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $order->payment_url = $paymentUrl;

        return $order;
    }

    public function updateOrder(Order $order, UpdateOrderData $data)
    {
        if (! Gate::forUser(auth('customers')->user())->allows('order-owner', $order)) {
            throw new UnauthorizedException('Access denied', 403);
        }

        return $this->orderRepository->update($order, [
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
            'notes' => $data->notes,
        ]);
    }

    public function cancelOrder(Order $order, CancelOrderData $data)
    {
        if (! Gate::forUser(auth('customers')->user())->allows('order-owner', $order)) {
            throw new UnauthorizedException('Access denied', 403);
        }

        if (! $this->orderRepository->canBeCancelled($order)) {
            throw new InvalidOrderStatusException('Order cannot be cancelled at current status: '.$order->getStatusLabel());
        }

        $this->orderService->cancelOrder($order, $data->reason);

        return $order->fresh();
    }

    public function getDeliveryOptions()
    {
        return $this->orderService->getDeliveryOptions();
    }

    public function getPaymentOptions()
    {
        return $this->orderService->getPaymentOptions();
    }

    public function calculateDelivery(DeliveryCalculateData $data)
    {
        $deliveryMethod = DeliveryMethod::find($data->deliveryMethod);
        $deliveryCost = $deliveryMethod?->cost ?? 0;
        $total = $data->cartTotal + $deliveryCost;

        return [
            'delivery_cost' => $deliveryCost,
            'total' => $total,
        ];
    }

    public function checkOrderForRepeat(int $orderId): array
    {
        $order = $this->getOrderById($orderId);

        $availableItems = collect();
        $unavailableItems = collect();

        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;

            if (! $product) {
                // Create a dummy Product object for missing products
                $dummyProduct = new Product;
                $dummyProduct->id = $orderItem->product_id;
                $dummyProduct->name = $orderItem->product_name;
                $dummyProduct->sku = $orderItem->product_sku;
                $dummyProduct->is_active = false;
                $dummyProduct->stock_quantity = 0;
                $dummyProduct->setOrderRepeatData($orderItem->id, $orderItem->quantity, 'inactive');

                $unavailableItems->push($dummyProduct);

                continue;
            }

            $availabilityStatus = $product->getAvailabilityStatus($orderItem->quantity);

            if ($availabilityStatus === 'available') {
                $availableItems->push($orderItem);
            } else {
                $unavailableProduct = $product->createUnavailableItem($orderItem->id, $orderItem->quantity, $availabilityStatus);
                $unavailableItems->push($unavailableProduct);
            }
        }

        return [
            'order' => $order,
            'available_items' => $availableItems,
            'unavailable_items' => $unavailableItems,
            'can_repeat' => $unavailableItems->isEmpty(),
        ];
    }

    public function repeatOrder(int $orderId): Order
    {
        $checkResult = $this->checkOrderForRepeat($orderId);

        if (! $checkResult['can_repeat']) {
            throw new InvalidOrderStatusException('Order cannot be repeated: some items are unavailable', 422);
        }

        $originalOrder = $checkResult['order'];

        return DB::transaction(function () use ($originalOrder, $checkResult) {
            $newOrder = $this->orderRepository->create([
                'customer_id' => $originalOrder->customer_id,
                'delivery_method_id' => $originalOrder->delivery_method_id,
                'delivery_address' => $originalOrder->delivery_address,
                'delivery_phone' => $originalOrder->delivery_phone,
                'delivery_notes' => $originalOrder->delivery_notes,
                'delivery_street' => $originalOrder->delivery_street,
                'delivery_city' => $originalOrder->delivery_city,
                'delivery_apartment' => $originalOrder->delivery_apartment,
                'delivery_postal_code' => $originalOrder->delivery_postal_code,
                'delivery_latitude' => $originalOrder->delivery_latitude,
                'delivery_longitude' => $originalOrder->delivery_longitude,
                'delivery_building' => $originalOrder->delivery_building,
                'delivery_entrance' => $originalOrder->delivery_entrance,
                'delivery_floor' => $originalOrder->delivery_floor,
                'payment_method_id' => $originalOrder->payment_method_id,
                'notes' => $originalOrder->notes,
                'subtotal' => $originalOrder->subtotal,
                'total_amount' => $originalOrder->total_amount,
                'shipping_amount' => $originalOrder->shipping_amount,
                'delivery_cost' => $originalOrder->delivery_cost,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create order items and update stock quantities
            foreach ($checkResult['available_items'] as $orderItem) {
                $product = $orderItem->product;

                $this->orderRepository->createOrderItem($newOrder, [
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product_name,
                    'product_sku' => $orderItem->product_sku,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $product->price,
                    'total_price' => $orderItem->quantity * $product->price,
                ]);

                // Update stock quantity using Product model
                $product->decrement('stock_quantity', $orderItem->quantity);
            }

            return $newOrder->fresh(['orderItems.product']);
        });
    }
}

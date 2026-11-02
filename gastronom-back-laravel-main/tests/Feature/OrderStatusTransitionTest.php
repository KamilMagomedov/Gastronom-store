<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSale;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_order_cannot_be_completed(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::CANCELLED->value,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $this->expectException(
            InvalidOrderStatusException::class
        );

        $orderService->updateOrderStatus(
            $order,
            OrderStatus::COMPLETED
        );
    }

    public function test_stale_order_instances_cannot_count_completed_sales_twice(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
          'status' => OrderStatus::READY->value,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 100.00,
            'total_price' => 300.00,
        ]);

        $firstStaleOrder = Order::query()->findOrFail($order->id);
        $secondStaleOrder = Order::query()->findOrFail($order->id);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $orderService->updateOrderStatus(
            $firstStaleOrder,
            OrderStatus::COMPLETED
        );

        $orderService->updateOrderStatus(
            $secondStaleOrder,
            OrderStatus::COMPLETED
        );

        $sale = ProductSale::query()
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame(
            3,
            $sale->total_quantity
        );

        $this->assertSame(
            '300.00',
            $sale->total_revenue
        );

        $this->assertSame(
            1,
            ProductSale::query()
                ->where('product_id', $product->id)
                ->count()
        );

        $this->assertSame(
            OrderStatus::COMPLETED->value,
            $order->fresh()->status
        );
    }

    public function test_completed_order_cannot_return_to_preparing(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED->value,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $this->expectException(
            InvalidOrderStatusException::class
        );

        $orderService->updateOrderStatus(
            $order,
            OrderStatus::PREPARING
        );
    }

    public function test_pending_order_cannot_jump_directly_to_delivering(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING->value,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $this->expectException(
            InvalidOrderStatusException::class
        );

        $orderService->updateOrderStatus(
            $order,
            OrderStatus::DELIVERING
        );
    }
}
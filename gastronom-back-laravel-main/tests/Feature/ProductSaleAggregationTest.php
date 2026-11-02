<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Database\UniqueConstraintViolationException;

class ProductSaleAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_sale_creates_product_sales_totals(): void
    {
        $product = Product::factory()->create();

        ProductSale::updateSales(
            $product->id,
            3,
            300.00
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
    }

    public function test_subsequent_sales_increment_existing_product_totals(): void
    {
        $product = Product::factory()->create();

        ProductSale::updateSales(
            $product->id,
            3,
            300.00
        );

        ProductSale::updateSales(
            $product->id,
            2,
            200.00
        );

        $sale = ProductSale::query()
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame(
            5,
            $sale->total_quantity
        );

        $this->assertSame(
            '500.00',
            $sale->total_revenue
        );

        $this->assertSame(
            1,
            ProductSale::query()
                ->where('product_id', $product->id)
                ->count()
        );
    }

    public function test_completing_order_records_product_sales_only_once(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PREPARING->value,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 100.00,
            'total_price' => 300.00,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $orderService->updateOrderStatus(
            $order,
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

        $this->assertNotNull(
            $order->fresh()->delivered_at
        );

        // Repeating the same completed status must not count sales again.
        $orderService->updateOrderStatus(
            $order->fresh(),
            OrderStatus::COMPLETED
        );

        $sale->refresh();

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
    }

    public function test_product_sales_allows_only_one_row_per_product(): void
    {
        $product = Product::factory()->create();

        ProductSale::create([
            'product_id' => $product->id,
            'total_quantity' => 3,
            'total_revenue' => 300.00,
        ]);

        $this->expectException(
            UniqueConstraintViolationException::class
        );

        ProductSale::create([
            'product_id' => $product->id,
            'total_quantity' => 2,
            'total_revenue' => 200.00,
        ]);
    }
}
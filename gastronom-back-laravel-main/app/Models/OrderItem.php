<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderItem $orderItem) {
            if ($orderItem->product && ! $orderItem->product_name) {
                $orderItem->product_name = $orderItem->product->name;
            }
            if ($orderItem->product && ! $orderItem->product_sku) {
                $orderItem->product_sku = $orderItem->product->sku;
            }

            if ($orderItem->quantity && $orderItem->unit_price) {
                $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
            }
        });

        static::saved(function (OrderItem $orderItem) {
            if ($orderItem->order) {
                $orderItem->order->recalculateTotals();
            }
        });

        static::deleted(function (OrderItem $orderItem) {
            if ($orderItem->order) {
                $orderItem->order->recalculateTotals();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

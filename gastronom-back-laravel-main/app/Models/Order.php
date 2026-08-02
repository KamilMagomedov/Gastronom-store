<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_amount',
        'subtotal',
        'shipping_amount',
        'delivery_method_id',
        'delivery_cost',
        'delivery_phone',
        'delivery_notes',
        'delivery_street',
        'delivery_city',
        'delivery_apartment',
        'delivery_postal_code',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_building',
        'delivery_entrance',
        'delivery_floor',
        'payment_method_id',
        'status',
        'payment_status',
        'notes',
        'delivered_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->status === OrderStatus::COMPLETED->value) {
                foreach ($order->orderItems as $item) {
                    ProductSale::updateSales(
                        $item->product_id,
                        $item->quantity,
                        $item->total_price
                    );
                }
            }
        });

        static::saved(function (Order $order) {
            $order->recalculateTotals();
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DeliveryMethod::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class);
    }

    public function getStatusLabel(): string
    {
        return OrderStatus::tryFrom($this->status)?->getLabel() ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return OrderStatus::tryFrom($this->status)?->getColor() ?? 'default';
    }

    public function getPaymentStatusLabel(): string
    {
        return PaymentStatus::tryFrom($this->payment_status)?->getLabel() ?? $this->payment_status;
    }

    public function getPaymentStatusColor(): string
    {
        return PaymentStatus::tryFrom($this->payment_status)?->getColor() ?? 'default';
    }

    public function getDeliveryCost(): float
    {
        return $this->delivery_cost ?? $this->deliveryMethod?->cost ?? 0.00;
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->orderItems()->sum('total_price');
        $shippingAmount = $this->shipping_amount ?? 0;
        $deliveryCost = $this->delivery_cost ?? 0;

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $shippingAmount + $deliveryCost,
        ]);
    }
}

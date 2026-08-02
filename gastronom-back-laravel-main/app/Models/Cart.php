<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'session_id',
        'total_amount',
        'total_items',
        'expires_at',
    ];

    protected $attributes = [
        'total_amount' => 0,
        'total_items' => 0,
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForSession($query, $sessionId = null)
    {
        return $sessionId
            ? $query->where('session_id', $sessionId)->whereNull('customer_id')
            : null;
    }

    public function findItem(int $productId)
    {
        return $this->items()->where('product_id', $productId)->first();
    }

    public function reset(): self
    {
        DB::transaction(function () {
            $this->items()->delete();

            $this->update([
                'total_amount' => 0,
                'total_items' => 0,
            ]);
        });

        return $this;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'acquirer_id',
        'gateway',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    public function acquirer(): BelongsTo
    {
        return $this->belongsTo(Acquirer::class);
    }

    public function getDisplayName(): string
    {
        if ($this->relationLoaded('acquirer') && $this->acquirer) {
            return $this->name.' ('.$this->acquirer->name.')';
        }

        return $this->name;
    }

    public function getAcquirerCodeAttribute(): ?string
    {
        if ($this->relationLoaded('acquirer') && $this->acquirer) {
            return $this->acquirer->code;
        }

        if ($this->gateway) {
            $parts = explode('_', $this->gateway, 2);

            return $parts[0];
        }

        return null;
    }

    public function getPaymentMethodTypeAttribute(): ?string
    {
        if ($this->gateway) {
            $parts = explode('_', $this->gateway, 2);

            return $parts[1] ?? null;
        }

        return null;
    }

    public static function getOptions(): array
    {
        return static::active()->sorted()->with('acquirer')->get()->mapWithKeys(function ($method) {
            return [$method->id => $method->getDisplayName()];
        })->toArray();
    }
}

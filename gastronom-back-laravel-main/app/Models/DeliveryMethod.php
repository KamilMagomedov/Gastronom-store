<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMethod extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
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

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public static function getOptions(): array
    {
        return static::active()->sorted()->get()->mapWithKeys(function ($method) {
            return [$method->id => $method->getDisplayName()];
        })->toArray();
    }
}

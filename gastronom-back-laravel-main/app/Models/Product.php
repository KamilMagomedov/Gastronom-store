<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'price_representation',
        'old_price_representation',
        'old_price',
        'sku',
        'external_id',
        'stock_quantity',
        'in_stock',
        'is_active',
        'weight',
        'unit',
        'category_id',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'in_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Temporary properties for order repeat
    public $order_item_id;

    public $requested_quantity;

    public $availability_reason;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->registerMediaConversions(function (\Spatie\MediaLibrary\MediaCollections\Models\Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(200)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(500)
                    ->height(500);

                $this->addMediaConversion('large')
                    ->width(1000)
                    ->height(1000);
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_product_attribute')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true)->where('stock_quantity', '>', 0);
    }

    public function hasDiscount(): bool
    {
        return $this->old_price && $this->old_price > $this->price;
    }

    public function getDiscountPercentage(): ?int
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        return round((($this->old_price - $this->price) / $this->old_price) * 100);
    }

    public function getMainImageUrl(): string
    {
        return $this->getFirstMediaUrl('images', 'medium') ?: '';
    }

    public function getThumbImageUrl(): string
    {
        return $this->getFirstMediaUrl('images', 'thumb') ?: '';
    }

    public function getAllImages(): array
    {
        return $this->getMedia('default', 'medium')->map(fn ($media) => $media->getUrl())->toArray();
    }

    public function productSale(): HasOne
    {
        return $this->hasOne(ProductSale::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->approved();
    }

    public function getAverageRating(): ?float
    {
        return $this->approvedReviews()->avg('rating');
    }

    public function getReviewsCount(): int
    {
        return $this->approvedReviews()->count();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->in_stock;
    }

    public function isNotAvailable(): bool
    {
        return ! $this->isAvailable();
    }

    public function isNotEnoughStockAvailable(int $quantity): bool
    {
        return $this->stock_quantity < $quantity;
    }

    public function isAvailableForRepeat(int $requestedQuantity): bool
    {
        return $this->is_active && $this->stock_quantity >= $requestedQuantity;
    }

    public function getAvailabilityStatus(int $requestedQuantity): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->stock_quantity < $requestedQuantity) {
            return 'out_of_stock';
        }

        return 'available';
    }

    public function getShortage(int $requestedQuantity): int
    {
        if ($this->stock_quantity >= $requestedQuantity) {
            return 0;
        }

        return $requestedQuantity - $this->stock_quantity;
    }

    public function setOrderRepeatData($orderItemId, $requestedQuantity, $availabilityReason = null): self
    {
        $this->order_item_id = $orderItemId;
        $this->requested_quantity = $requestedQuantity;
        $this->availability_reason = $availabilityReason;

        return $this;
    }

    public function createUnavailableItem($orderItemId, $requestedQuantity, $reason): self
    {
        $clone = clone $this;
        $clone->setOrderRepeatData($orderItemId, $requestedQuantity, $reason);

        return $clone;
    }
}

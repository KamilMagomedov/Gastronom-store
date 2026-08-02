<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Category extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'external_id',
        'is_system',
        'sort_order',
        'is_active',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(50)
                    ->height(50)
                    ->sharpen(10);

                $this->addMediaConversion('small')
                    ->width(100)
                    ->height(100);
            });
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotSystem($query)
    {
        return $query->where('is_system', false);
    }

    public function canBeDeleted(): bool
    {
        return ! $this->is_system;
    }

    public function getIconUrl(): string
    {
        return $this->getFirstMediaUrl('icon', 'thumb') ?: '';
    }

    public function getIconUrlSmall(): string
    {
        return $this->getFirstMediaUrl('icon', 'small') ?: '';
    }
}

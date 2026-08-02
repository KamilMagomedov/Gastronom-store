<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaticPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'is_active',
    ];

    protected static array $protectedSlugs = ['terms', 'privacy'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(StaticPageSection::class)->orderBy('number');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function isProtected(): bool
    {
        return in_array($this->slug, static::$protectedSlugs);
    }
}

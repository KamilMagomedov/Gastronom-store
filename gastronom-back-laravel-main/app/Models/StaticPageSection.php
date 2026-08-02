<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaticPageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'static_page_id',
        'number',
        'title',
        'content',
        'important_note',
    ];

    public function staticPage(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(StaticPageSectionRequirement::class);
    }
}

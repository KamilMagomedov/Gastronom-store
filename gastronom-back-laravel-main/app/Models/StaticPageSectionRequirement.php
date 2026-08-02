<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticPageSectionRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'static_page_section_id',
        'requirement',
        'order',
    ];

    public function staticPageSection(): BelongsTo
    {
        return $this->belongsTo(StaticPageSection::class);
    }
}

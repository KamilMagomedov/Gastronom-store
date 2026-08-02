<?php

namespace App\Models;

use App\Enums\SupportStatus;
use App\Enums\SupportTheme;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = [
        'theme',
        'order_id',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'theme' => SupportTheme::class,
            'status' => SupportStatus::class,
        ];
    }
}

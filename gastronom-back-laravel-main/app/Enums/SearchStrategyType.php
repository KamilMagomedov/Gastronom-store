<?php

namespace App\Enums;

use App\Strategies\SearchEloquentStrategy;

enum SearchStrategyType: string
{
    case ELOQUENT = 'eloquent';

    public function strategy(): string
    {
        return match ($this) {
            self::ELOQUENT => SearchEloquentStrategy::class,
        };
    }
}

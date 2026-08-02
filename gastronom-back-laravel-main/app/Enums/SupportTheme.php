<?php

namespace App\Enums;

enum SupportTheme: string
{
    case ORDER = 'order';
    case DELIVERY = 'delivery';
    case RETURN = 'return';

    case OTHER = 'other';

    public function label(): string
    {
        return __('enums.support_theme.'.$this->value);
    }
}

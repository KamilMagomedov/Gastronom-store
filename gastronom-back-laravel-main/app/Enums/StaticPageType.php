<?php

namespace App\Enums;

enum StaticPageType: string
{
    case TERMS = 'terms';
    case PRIVACY = 'privacy';

    public function getTitle(): string
    {
        return match ($this) {
            self::TERMS => 'Условия использования',
            self::PRIVACY => 'Политика конфиденциальности',
        };
    }
}

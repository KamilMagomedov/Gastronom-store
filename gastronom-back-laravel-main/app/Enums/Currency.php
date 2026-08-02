<?php

namespace App\Enums;

enum Currency: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case EUR = 'EUR';

    public function getLabel(): string
    {
        return match ($this) {
            self::RUB => 'Российский рубль',
            self::USD => 'Доллар США',
            self::EUR => 'Евро',
        };
    }

    public function getSymbol(): string
    {
        return match ($this) {
            self::RUB => '₽',
            self::USD => '$',
            self::EUR => '€',
        };
    }
}

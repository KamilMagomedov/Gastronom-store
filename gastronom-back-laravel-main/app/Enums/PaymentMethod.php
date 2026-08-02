<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MOBILE_APP = 'mobile_app';
    case CARD = 'card';
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::MOBILE_APP => 'Мобильное приложение',
            self::CARD => 'Карта',
            self::CASH => 'Наличные',
            self::BANK_TRANSFER => 'Банковский перевод',
        };
    }
}

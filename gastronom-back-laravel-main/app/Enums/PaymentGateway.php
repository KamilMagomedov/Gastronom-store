<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case SBERBANK = 'sberbank';
    case TINKOFF = 'tinkoff';
    case VTB = 'vtb';
    case ALFA = 'alfa';
    case PSB = 'psb';
    case GAZPROM = 'gazprom';
    case STRIPE = 'stripe';

    public function getLabel(): string
    {
        return match ($this) {
            self::SBERBANK => 'Сбербанк',
            self::TINKOFF => 'Тинькофф',
            self::VTB => 'ВТБ',
            self::ALFA => 'Альфа-Банк',
            self::PSB => 'ПСБ',
            self::GAZPROM => 'Газпромбанк',
            self::STRIPE => 'Stripe',
        };
    }
}

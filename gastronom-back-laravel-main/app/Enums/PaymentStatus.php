<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачен',
            self::FAILED => 'Ошибка оплаты',
            self::REFUNDED => 'Возвращен',
            self::PARTIALLY_REFUNDED => 'Частично возвращен',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'warning',
            self::PARTIALLY_REFUNDED => 'warning',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}

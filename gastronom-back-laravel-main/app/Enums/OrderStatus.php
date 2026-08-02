<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case DELIVERING = 'delivering';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает обработки',
            self::CONFIRMED => 'Подтвержден',
            self::PREPARING => 'Готовится',
            self::READY => 'Готов к выдаче',
            self::DELIVERING => 'Доставляется',
            self::COMPLETED => 'Завершен',
            self::CANCELLED => 'Отменен',
            self::REFUNDED => 'Возвращен',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::PREPARING => 'primary',
            self::READY => 'success',
            self::DELIVERING => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'warning',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}

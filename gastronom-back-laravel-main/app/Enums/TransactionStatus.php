<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.transaction.status.pending'),
            self::PROCESSING => __('enums.transaction.status.processing'),
            self::COMPLETED => __('enums.transaction.status.completed'),
            self::FAILED => __('enums.transaction.status.failed'),
            self::CANCELLED => __('enums.transaction.status.cancelled'),
            self::REFUNDED => __('enums.transaction.status.refunded'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'warning',
        };
    }
}

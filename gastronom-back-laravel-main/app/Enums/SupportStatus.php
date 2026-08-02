<?php

namespace App\Enums;

enum SupportStatus: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return __('enums.support_status.'.$this->value);
    }
}

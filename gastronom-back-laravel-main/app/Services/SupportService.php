<?php

namespace App\Services;

use App\Enums\SupportStatus;
use App\Models\Support;

class SupportService
{
    public function create(string $theme, string $message, null|string|int $orderId): Support
    {
        return Support::query()->create([
            'theme' => $theme,
            'order_id' => $orderId,
            'message' => $message,
            'status' => SupportStatus::NEW->value,
        ]);
    }
}

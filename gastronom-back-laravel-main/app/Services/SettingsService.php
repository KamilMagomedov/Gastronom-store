<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\PaymentMethod;
use App\Models\Setting;

class SettingsService
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function getSettings()
    {
        $dbSettings = Setting::public()->get()->pluck('value', 'key');

        return [
            // App settings from database
            'app_name' => $dbSettings->get('app_name', 'Home Delivery App'),
            'app_version' => $dbSettings->get('app_version', '1.0.0'),
            'currency' => $dbSettings->get('currency', 'RUB'),

            // Contact settings from database
            'contact_email' => $dbSettings->get('contact_email', 'support@example.com'),
            'contact_phone' => $dbSettings->get('contact_phone', '+74951234567'),

            // Social links from database
            'social_links' => $dbSettings->get('social_links', []),

            // Delivery settings from database
            'delivery_settings' => [
                'min_order_amount' => $dbSettings->get('min_order_amount'),
                'free_delivery_threshold' => $dbSettings->get('free_delivery_threshold'),
                'delivery_fee' => $dbSettings->get('delivery_fee'),
            ],

            // Dynamic data from services
            'delivery_methods' => $this->orderService->getDeliveryOptions(),
            'payment_methods' => PaymentMethod::active()->sorted()->with('acquirer')->get()->map(fn ($method) => [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'acquirer' => $method->acquirer ? [
                    'id' => $method->acquirer->id,
                    'name' => $method->acquirer->name,
                    'code' => $method->acquirer->code,
                ] : null,
            ]),
            'order_statuses' => collect(OrderStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ]),
            'payment_statuses' => collect(PaymentStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ]),
        ];
    }
}

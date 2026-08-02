<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Support\Collection;

class PaymentManager
{
    private array $gateways = [];

    public function __construct() {}

    public function register(string $name, PaymentGateway $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function gateway(?string $name = null): PaymentGateway
    {
        $name = $name ?? '';

        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        throw new PaymentException("Payment gateway '{$name}' is not registered.");
    }

    public function resolve(string $name): PaymentGateway
    {
        try {
            return $this->gateway($name);
        } catch (PaymentException) {
            $acquirer = \App\Models\Acquirer::where('code', $name)
                ->where('is_active', true)
                ->first();

            if ($acquirer) {
                return $acquirer->gateway();
            }

            throw new PaymentException("Payment gateway '{$name}' not found.");
        }
    }

    public function gatewayForPaymentMethod(PaymentMethod $paymentMethod): ?PaymentGateway
    {
        if (! $this->isOnlinePayment($paymentMethod)) {
            return null;
        }

        if ($paymentMethod->relationLoaded('acquirer') && $paymentMethod->acquirer) {
            try {
                return $paymentMethod->acquirer->gateway();
            } catch (PaymentException) {
                return null;
            }
        }

        if ($paymentMethod->gateway) {
            $code = $this->resolveGatewayName($paymentMethod->gateway);

            return $this->resolveByCode($code);
        }

        return null;
    }

    public function getPaymentMethodType(PaymentMethod $paymentMethod): ?string
    {
        return $paymentMethod->payment_method_type;
    }

    public function isOnlinePayment(PaymentMethod $paymentMethod): bool
    {
        if ($paymentMethod->relationLoaded('acquirer') && $paymentMethod->acquirer) {
            return true;
        }

        return $paymentMethod->gateway !== null;
    }

    public function getAvailableGateways(): Collection
    {
        return \App\Models\Acquirer::active()->get()->map(fn ($acquirer) => $acquirer->gateway())
            ->filter(fn ($gateway) => $gateway->isAvailable());
    }

    public function initiateOnlinePayment(Order $order, ?string $paymentMethodType = null): ?array
    {
        $paymentMethod = $order->paymentMethod;

        if (! $paymentMethod || ! $this->isOnlinePayment($paymentMethod)) {
            return null;
        }

        $gateway = $this->gatewayForPaymentMethod($paymentMethod);

        if (! $gateway || ! $gateway->isAvailable()) {
            throw new PaymentException('Payment gateway is not available');
        }

        $type = $paymentMethodType ?? $this->getPaymentMethodType($paymentMethod);

        if ($type && method_exists($gateway, 'createPayment')) {
            return $gateway->createPayment($order);
        }

        return $gateway->createPayment($order);
    }

    private function resolveGatewayName(string $gateway): string
    {
        $parts = explode('_', $gateway, 2);

        return $parts[0];
    }

    private function resolveByCode(string $code): ?PaymentGateway
    {
        $acquirer = \App\Models\Acquirer::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $acquirer) {
            return null;
        }

        try {
            return $acquirer->gateway();
        } catch (PaymentException) {
            return null;
        }
    }
}

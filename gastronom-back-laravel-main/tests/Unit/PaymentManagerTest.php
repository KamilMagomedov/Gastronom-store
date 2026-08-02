<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_exception_for_unknown_gateway(): void
    {
        $this->expectException(PaymentException::class);

        $manager = app(PaymentManager::class);
        $manager->gateway('nonexistent');
    }

    public function test_register_and_retrieve_gateway(): void
    {
        $manager = app(PaymentManager::class);

        $mock = $this->createMock(PaymentGateway::class);
        $mock->method('getGatewayName')->willReturn('sberbank');
        $mock->method('isAvailable')->willReturn(true);

        $manager->register('sberbank', $mock);

        $gateway = $manager->gateway('sberbank');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertEquals('sberbank', $gateway->getGatewayName());
    }

    public function test_gateway_for_online_payment_method_resolves_via_acquirer(): void
    {
        $manager = app(PaymentManager::class);

        $paymentMethod = PaymentMethod::factory()->online()->create();

        $gateway = $manager->gatewayForPaymentMethod($paymentMethod);

        $this->assertNotNull($gateway);
        $this->assertInstanceOf(PaymentGateway::class, $gateway);
    }

    public function test_gateway_for_cash_returns_null(): void
    {
        $manager = app(PaymentManager::class);

        $paymentMethod = PaymentMethod::factory()->cash()->create();

        $gateway = $manager->gatewayForPaymentMethod($paymentMethod);

        $this->assertNull($gateway);
    }

    public function test_is_online_payment(): void
    {
        $manager = app(PaymentManager::class);

        $online = PaymentMethod::factory()->online()->create();
        $cash = PaymentMethod::factory()->cash()->create();

        $this->assertTrue($manager->isOnlinePayment($online));
        $this->assertFalse($manager->isOnlinePayment($cash));
    }

    public function test_get_payment_method_type(): void
    {
        $manager = app(PaymentManager::class);

        $card = PaymentMethod::factory()->online()->create();
        $sbp = PaymentMethod::factory()->sbp()->create();
        $cash = PaymentMethod::factory()->cash()->create();

        $this->assertNull($manager->getPaymentMethodType($card));
        $this->assertEquals('sbp', $manager->getPaymentMethodType($sbp));
        $this->assertNull($manager->getPaymentMethodType($cash));
    }

    public function test_resolve_returns_gateway_from_acquirer(): void
    {
        $manager = app(PaymentManager::class);

        $acquirer = \App\Models\Acquirer::factory()->sberbank()->create();

        $gateway = $manager->resolve('sberbank');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertEquals('sberbank', $gateway->getGatewayName());
    }

    public function test_resolve_throws_for_unknown_gateway(): void
    {
        $this->expectException(PaymentException::class);

        $manager = app(PaymentManager::class);
        $manager->resolve('nonexistent_gateway');
    }

    public function test_register_custom_gateway(): void
    {
        $manager = app(PaymentManager::class);

        $mock = $this->createMock(PaymentGateway::class);
        $mock->method('getGatewayName')->willReturn('test_gateway');
        $mock->method('isAvailable')->willReturn(true);

        $manager->register('test_gateway', $mock);

        $this->assertSame($mock, $manager->gateway('test_gateway'));
    }

    public function test_throws_exception_when_initiating_non_online_payment(): void
    {
        $manager = app(PaymentManager::class);

        $paymentMethod = PaymentMethod::factory()->cash()->create();
        $order = \App\Models\Order::factory()->create([
            'payment_method_id' => $paymentMethod->id,
        ]);

        $result = $manager->initiateOnlinePayment($order);

        $this->assertNull($result);
    }
}

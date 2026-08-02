<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_contains_gateway(): void
    {
        $paymentMethod = new PaymentMethod;

        $this->assertContains('gateway', $paymentMethod->getFillable());
    }

    public function test_scope_active_returns_only_active_methods(): void
    {
        PaymentMethod::factory()->create(['is_active' => true, 'sort_order' => 1]);
        PaymentMethod::factory()->create(['is_active' => false, 'sort_order' => 2]);

        $activeMethods = PaymentMethod::active()->get();

        $this->assertCount(1, $activeMethods);
    }

    public function test_scope_sorted_returns_ordered_by_sort_order(): void
    {
        PaymentMethod::factory()->create(['sort_order' => 3, 'name' => 'Last']);
        PaymentMethod::factory()->create(['sort_order' => 1, 'name' => 'First']);
        PaymentMethod::factory()->create(['sort_order' => 2, 'name' => 'Second']);

        $methods = PaymentMethod::sorted()->get();

        $this->assertEquals('First', $methods[0]->name);
        $this->assertEquals('Second', $methods[1]->name);
        $this->assertEquals('Last', $methods[2]->name);
    }

    public function test_online_payment_method_with_gateway(): void
    {
        $method = PaymentMethod::factory()->online()->create();

        $this->assertEquals('sberbank', $method->gateway);
        $this->assertTrue($method->is_active);
    }

    public function test_cash_payment_method_without_gateway(): void
    {
        $method = PaymentMethod::factory()->cash()->create();

        $this->assertNull($method->gateway);
        $this->assertTrue($method->is_active);
    }

    public function test_sbp_payment_method(): void
    {
        $method = PaymentMethod::factory()->sbp()->create();

        $this->assertEquals('tinkoff_sbp', $method->gateway);
        $this->assertTrue($method->is_active);
    }

    public function test_get_options_returns_only_active_sorted(): void
    {
        PaymentMethod::factory()->create([
            'name' => 'Active 2',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        PaymentMethod::factory()->create([
            'name' => 'Active 1',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PaymentMethod::factory()->create([
            'name' => 'Inactive',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $options = PaymentMethod::getOptions();

        $this->assertCount(2, $options);
    }

    public function test_online_payment_method_has_acquirer(): void
    {
        $method = PaymentMethod::factory()->online()->create();

        $this->assertNotNull($method->acquirer_id);
        $this->assertNotNull($method->acquirer);
        $this->assertEquals('sberbank', $method->acquirer->code);
    }

    public function test_sbp_payment_method_has_acquirer(): void
    {
        $method = PaymentMethod::factory()->sbp()->create();

        $this->assertNotNull($method->acquirer_id);
        $this->assertNotNull($method->acquirer);
        $this->assertEquals('tinkoff', $method->acquirer->code);
    }

    public function test_can_create_method_with_gateway_via_create(): void
    {
        $method = PaymentMethod::create([
            'name' => 'Test Online',
            'description' => 'Test',
            'gateway' => 'sberbank',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertEquals('sberbank', $method->gateway);
        $this->assertDatabaseHas('payment_methods', [
            'id' => $method->id,
            'gateway' => 'sberbank',
        ]);
    }
}

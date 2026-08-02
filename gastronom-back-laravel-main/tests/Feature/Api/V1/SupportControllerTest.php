<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SupportStatus;
use App\Enums\SupportTheme;
use App\Models\Support;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.support.store');
    }

    public function test_it_creates_support_message_successfully(): void
    {
        $payload = [
            'theme' => SupportTheme::ORDER->value,
            'order_id' => 1,
            'message' => 'Test message to support',
        ];

        $response = $this->postJson($this->route, $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);

        $this->assertDatabaseCount('supports', 1);

        $support = Support::query()->first();

        $this->assertEqualsIgnoringCase(SupportTheme::ORDER->value, $support->theme->value);
        $this->assertEqualsIgnoringCase(SupportStatus::NEW->value, $support->status->value);
        $this->assertEquals(1, $support->order_id);
        $this->assertEquals('Test message to support', $support->message);
    }

    public function test_it_fails_with_invalid_theme(): void
    {
        $payload = [
            'theme' => 'invalid-theme',
            'order_id' => 1,
            'message' => 'Test message to support',
        ];

        $response = $this->postJson($this->route, $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'data' => [],
            ]);

        $response->assertJsonValidationErrors('theme');

        $this->assertDatabaseCount('supports', 0);
    }

    public function test_it_fails_when_message_is_missing(): void
    {
        $payload = [
            'theme' => SupportTheme::ORDER->value,
            'order_id' => 1,
        ];

        $response = $this->postJson($this->route, $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'data' => [],
            ]);

        $response->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('supports', 0);
    }

    public function test_it_requires_theme_field(): void
    {
        $payload = [
            'order_id' => 1,
            'message' => 'Test message',
        ];

        $response = $this->postJson($this->route, $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'data' => [],
            ]);

        $response->assertJsonValidationErrors('theme');

        $this->assertDatabaseCount('supports', 0);
    }

    public function test_it_can_handle_null_order_id(): void
    {
        $payload = [
            'theme' => SupportTheme::DELIVERY->value,
            'message' => 'General support message',
        ];

        $response = $this->postJson($this->route, $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);

        $this->assertDatabaseCount('supports', 1);

        $support = Support::first();
        $this->assertEqualsIgnoringCase(SupportTheme::DELIVERY->value, $support->theme->value);
        $this->assertEqualsIgnoringCase(SupportStatus::NEW->value, $support->status->value);
        $this->assertNull($support->order_id);
        $this->assertEquals('General support message', $support->message);
    }
}

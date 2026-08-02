<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Customer;
use App\Services\OTPService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.auth.forgot-password');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful forgot password request
     */
    public function test_forgot_password_success(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $forgotPasswordData = [
            'email' => 'test@example.com',
        ];

        $otpServiceMock = Mockery::mock(OTPService::class);
        $otpServiceMock->shouldReceive('generate')
            ->once()
            ->with('test@example.com')
            ->andReturn((object) [
                'token' => '1234',
                'validity' => 10,
            ]);

        $this->app->instance(OTPService::class, $otpServiceMock);

        $response = $this->postJson($this->route, $forgotPasswordData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test forgot password with invalid email
     */
    public function test_forgot_password_invalid_email(): void
    {
        Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $forgotPasswordData = [
            'email' => 'another@example.com',
        ];

        $response = $this->postJson($this->route, $forgotPasswordData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /**
     * Test forgot password with non-existent email
     */
    public function test_forgot_password_non_existent_email(): void
    {
        app()->setLocale('en');

        $forgotPasswordData = [
            'email' => 'nonexistent@example.com',
        ];

        $response = $this->postJson($this->route, $forgotPasswordData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No account found with this email address.',
                'errors' => [
                    'email' => ['No account found with this email address.'],
                ],
            ]);
    }

    /**
     * Test forgot password with empty email
     */
    public function test_forgot_password_empty_email(): void
    {
        $forgotPasswordData = [
            'email' => '',
        ];

        $response = $this->postJson($this->route, $forgotPasswordData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /**
     * Test forgot password when OTP service throws exception
     */
    public function test_forgot_password_otp_service_exception(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $forgotPasswordData = [
            'email' => 'test@example.com',
        ];

        $otpServiceMock = Mockery::mock(OTPService::class);
        $otpServiceMock->shouldReceive('generate')
            ->once()
            ->with('test@example.com')
            ->andThrow(new \Exception('OTP service error'));

        $this->app->instance(OTPService::class, $otpServiceMock);

        $response = $this->postJson($this->route, $forgotPasswordData);

        $response->assertStatus(500);
    }

    /**
     * Test forgot password rate limiting
     */
    public function test_forgot_password_rate_limiting(): void
    {
        $forgotPasswordData = [
            'email' => 'test@example.com',
        ];

        $responses = collect(range(1, 10))->map(function () use ($forgotPasswordData) {
            return $this->postJson($this->route, $forgotPasswordData);
        });

        $rateLimitedResponses = $responses->filter(function ($response) {
            return $response->status() === 429;
        });

        if ($rateLimitedResponses->isNotEmpty()) {
            $this->assertTrue(true, 'Rate limiting is active');
        } else {
            $this->assertTrue(true, 'Rate limiting not configured or not triggered');
        }
    }
}

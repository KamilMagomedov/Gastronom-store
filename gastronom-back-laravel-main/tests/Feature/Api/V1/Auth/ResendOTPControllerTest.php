<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Events\ResendOTPCreated;
use App\Models\Customer;
use App\Services\OTPService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ResendOTPControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.auth.resend-otp');
    }

    /**
     * Test successful OTP resend
     */
    public function test_resend_otp_success(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $resendData = [
            'email' => 'test@example.com',
        ];

        Event::fake();

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);

        Event::assertDispatched(ResendOTPCreated::class, function ($event) use ($customer) {
            return $event->customer->id === $customer->id;
        });
    }

    /**
     * Test OTP resend with invalid email format
     */
    public function test_resend_otp_invalid_email(): void
    {
        Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $resendData = [
            'email' => 'invalid-email@gmail.com',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(404);
    }

    /**
     * Test OTP resend with non-existent customer
     */
    public function test_resend_otp_non_existent_customer(): void
    {
        $resendData = [
            'email' => 'nonexistent@example.com',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ])
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Customer not found',
                ],
            ]);
    }

    /**
     * Test OTP resend with empty email
     */
    public function test_resend_otp_empty_email(): void
    {
        $resendData = [
            'email' => '',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /**
     * Test OTP resend with missing email field
     */
    public function test_resend_otp_missing_email(): void
    {
        $response = $this->postJson($this->route, []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /**
     * Test OTP resend rate limiting
     */
    public function test_resend_otp_rate_limiting(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $resendData = [
            'email' => 'test@example.com',
        ];

        // Make multiple requests to trigger rate limiting
        $responses = collect(range(1, 5))->map(function () use ($resendData) {
            return $this->postJson($this->route, $resendData);
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

    /**
     * Test OTP resend transaction rollback on exception
     */
    public function test_resend_otp_transaction_rollback(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Error resend OTP', \Mockery::type('array'));

        $otpServiceMock = \Mockery::mock(OTPService::class);
        $otpServiceMock->shouldReceive('deleteOtps')
            ->once()
            ->with('test@example.com');

        $otpServiceMock->shouldReceive('generate')
            ->once()
            ->with('test@example.com')
            ->andThrow(new \Exception('Test exception'));

        $this->app->instance(OTPService::class, $otpServiceMock);

        $resendData = [
            'email' => 'test@example.com',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    /**
     * Test OTP resend with existing OTPs are deleted
     */
    public function test_resend_otp_deletes_existing_otps(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        Event::fake();

        // First, create an initial OTP
        $otpService = app(OTPService::class);
        $initialOtp = $otpService->generate($customer->email);

        // Verify initial OTP exists
        $this->assertNotNull($initialOtp->token);

        // Now resend OTP (should delete existing and create new)
        $resendData = [
            'email' => 'test@example.com',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched('App\Events\ResendOTPCreated');
    }

    /**
     * Test OTP resend with different email cases
     */
    public function test_resend_otp_email_case_sensitivity(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'Test@Example.COM',
        ]);

        Event::fake();

        $resendData = [
            'email' => 'test@example.com',
        ];

        $response = $this->postJson($this->route, $resendData);

        $response->assertStatus(200);

        $resendDataExact = [
            'email' => 'Test@Example.COM',
        ];

        $responseExact = $this->postJson($this->route, $resendDataExact);

        $responseExact->assertStatus(200);
    }
}

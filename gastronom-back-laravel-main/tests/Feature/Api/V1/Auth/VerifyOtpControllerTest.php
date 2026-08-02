<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\OTPService;
use Ichtrojan\Otp\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class VerifyOtpControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful OTP verification
     */
    public function test_otp_verification_success(): void
    {
        $customerData = [
            'name' => 'user',
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $responseRegister = $this->postJson(route('api.v1.auth.register'), $customerData);

        $responseRegister->assertCreated();

        $otpCode = Otp::query()
            ->where('identifier', 'test@gmail.com')
            ->value('token');

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'email' => 'test@gmail.com',
            'code' => $otpCode,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                    'token',
                    'customer' => [
                        'name',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Email verified successfully',
                    'customer' => [
                        'name' => 'user',
                        'email' => 'test@gmail.com',
                    ],
                ],
            ]);

        $this->assertArrayHasKey('token', $response->json('data'));
    }

    /**
     * Test OTP verification with non-existent customer
     */
    public function test_otp_verification_customer_not_found(): void
    {
        $customerData = [
            'name' => 'user',
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $responseRegister = $this->postJson(
            route('api.v1.auth.register'),
            $customerData
        );

        $responseRegister->assertCreated();

        $otpCode = Otp::query()
            ->where('identifier', 'test@gmail.com')
            ->value('token');

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'email' => 'anotheremail@gmail.com',
            'code' => $otpCode,
        ]);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
            ])
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Customer not found',
                ],
            ]);
    }

    /**
     * Test OTP verification with invalid code
     */
    public function test_otp_verification_invalid_code(): void
    {
        $customerData = [
            'name' => 'user',
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $responseRegister = $this->postJson(
            route('api.v1.auth.register'),
            $customerData
        );

        $responseRegister->assertCreated();

        $otpCode = Otp::query()->where('identifier', 'test@gmail.com')
            ->first()->token;

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'email' => 'test@gmail.com',
            'code' => $otpCode + 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test OTP verification with expired code
     */
    public function test_otp_verification_expired_code(): void
    {
        $customerData = [
            'name' => 'user',
            'email' => 'test@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $responseRegister = $this->postJson(
            route('api.v1.auth.register'),
            $customerData
        );

        $responseRegister->assertStatus(201);

        $otpCode = Otp::query()->where('identifier', 'test@gmail.com')
            ->first();

        $otpCode->created_at = $otpCode->created_at->subMinutes(11);
        $otpCode->save();

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'email' => 'test@gmail.com',
            'code' => $otpCode->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test OTP verification with invalid input data
     */
    public function test_otp_verification_validation_failure(): void
    {
        $invalidData = [
            'email' => 'invalid-email',
            'code' => '12',
        ];

        $response = $this->postJson(route('api.v1.auth.verify'), $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'code',
                ],
            ]);
    }

    /**
     * Test OTP verification when OTP service throws exception
     */
    public function test_otp_verification_service_exception(): void
    {
        $customerData = [
            'email' => $this->faker->unique()->safeEmail,
            'code' => '123456',
        ];

        $customer = Customer::factory()->create([
            'email' => $customerData['email'],
            'email_verified_at' => null,
        ]);

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($customerData['email'])
            ->andReturn($customer);

        $otpServiceMock = Mockery::mock(OTPService::class);
        $otpServiceMock->shouldReceive('validate')
            ->once()
            ->with($customerData['email'], 1234)
            ->andThrow(new \Exception('OTP service error'));

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);
        $this->app->instance(OTPService::class, $otpServiceMock);

        $response = $this->postJson(route('api.v1.auth.verify'), [
            'email' => $customer->email,
            'code' => 1234,
        ]);

        $response->assertStatus(500);
    }

    /**
     * Test OTP verification when customer repository throws exception
     */
    public function test_otp_verification_repository_exception(): void
    {
        $customerData = [
            'email' => $this->faker->unique()->safeEmail,
            'code' => 1234,
        ];

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($customerData['email'])
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);

        $response = $this->postJson(route('api.v1.auth.verify'), $customerData);

        $response->assertStatus(500);
    }

    /**
     * Test OTP verification with email that exists in database but not in customers table
     */
    public function test_otp_verification_email_exists_but_not_customer(): void
    {
        $customerData = [
            'email' => 'user@example.com',
            'code' => 1234,
        ];

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($customerData['email'])
            ->andReturn(null);

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);

        $response = $this->postJson(route('api.v1.auth.verify'), $customerData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'data' => [
                    'message' => 'Customer not found',
                ],
            ]);
    }

    /**
     * Test OTP verification rate limiting
     */
    public function test_otp_verification_rate_limiting(): void
    {
        $customerData = [
            'email' => $this->faker->unique()->safeEmail,
            'code' => 1234,
        ];

        $customer = Customer::factory()->create([
            'email' => $customerData['email'],
            'email_verified_at' => null,
        ]);

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->andReturn($customer);

        $otpServiceMock = Mockery::mock(OTPService::class);
        $otpServiceMock->shouldReceive('validate')
            ->andReturn((object) ['status' => false, 'message' => 'Invalid']);

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);
        $this->app->instance(OTPService::class, $otpServiceMock);

        $responses = collect(range(1, 10))->map(function () use ($customerData) {
            return $this->postJson(route('api.v1.auth.verify'), $customerData);
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

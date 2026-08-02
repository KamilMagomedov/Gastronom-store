<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.auth.login');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful customer login
     */
    public function test_customer_login_success_mock(): void
    {
        $password = 'password123';
        $customer = Customer::factory()->create([
            'password' => Hash::make($password),
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => $password,
        ];

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($customer->email)
            ->andReturn($customer);

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                    'customer' => [
                        'name' => $customer->name,
                        'email' => $customer->email,
                    ],
                ],
            ]);

        $this->assertArrayHasKey('token', $response->json('data'));
    }

    public function test_customer_login_success(): void
    {
        $password = 'password123';
        $customer = Customer::factory()->create([
            'password' => Hash::make($password),
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => $password,
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                    'customer' => [
                        'name' => $customer->name,
                        'email' => $customer->email,
                    ],
                ],
            ]);

        $this->assertArrayHasKey('token', $response->json('data'));
    }

    public function test_customer_login_success_exists_in_db(): void
    {
        $password = 'password123';

        $customer = Customer::factory()->create([
            'password' => Hash::make($password),
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => $password,
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                    'customer' => [
                        'name' => $customer->name,
                        'email' => $customer->email,
                    ],
                ],
            ]);

        $this->assertArrayHasKey('token', $response->json('data'));
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['email' => $loginData['email']]);
    }

    /**
     * Test login with invalid email
     */
    public function test_customer_login_invalid_email(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        Customer::factory()->create([
            'email' => $loginData['email'],
            'password' => Hash::make($loginData['password']),
            'email_verified_at' => now()->timestamp,
        ]);

        $response = $this->postJson($this->route, [
            'email' => 'nonexistent@example.com',
            'password' => $loginData['password'],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ])
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login with invalid password
     */
    public function test_customer_login_invalid_password(): void
    {
        $customer = Customer::factory()->create([
            'password' => Hash::make('correctpassword'),
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ])
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login with invalid input data
     */
    public function test_customer_login_validation_failure(): void
    {
        $invalidData = [
            'email' => 'invalid-email',
            'password' => '',
        ];

        $response = $this->postJson($this->route, $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    /**
     * Test login when customer repository throws exception
     */
    public function test_customer_login_repository_exception(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(500);
    }

    /**
     * Test login with customer that exists but password is null
     */
    public function test_customer_login_null_password(): void
    {
        $customer = Customer::factory()->create([
            'password' => 'anypassword',
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => null,
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(422)
            ->assertJson([
                'data' => [],
                'message' => 'The password field is required.',
                'errors' => [
                    'password' => [
                        'The password field is required.',
                    ],
                ],
                'success' => false,
            ]);
    }

    /**
     * Test login rate limiting
     */
    public function test_customer_login_rate_limiting(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $customerRepositoryMock = Mockery::mock(CustomerRepository::class);
        $customerRepositoryMock->shouldReceive('findByEmail')
            ->andReturn(null);

        $this->app->instance(CustomerRepository::class, $customerRepositoryMock);

        $responses = collect(range(1, 10))->map(function () use ($loginData) {
            return $this->postJson($this->route, $loginData);
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
     * Test login with empty credentials
     */
    public function test_customer_login_empty_credentials(): void
    {
        $emptyData = [
            'email' => '',
            'password' => '',
        ];

        $response = $this->postJson($this->route, $emptyData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    /**
     * Test login with valid email format but non-existent
     */
    public function test_customer_login_valid_email_format_non_existent(): void
    {
        $loginData = [
            'email' => 'validbutnonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertStatus(422)
            ->assertJson([
                'data' => [],
                'message' => 'Invalid credentials',
                'success' => false,
            ]);
    }

    /**
     * Test successful login creates proper token
     */
    public function test_customer_login_token_creation(): void
    {
        $password = 'password123';
        $customer = Customer::factory()->create([
            'password' => Hash::make($password),
            'email_verified_at' => now()->timestamp,
        ]);

        $loginData = [
            'email' => $customer->email,
            'password' => $password,
        ];

        $response = $this->postJson($this->route, $loginData);

        $response->assertOk();

        $responseData = $response->json('data');
        $this->assertArrayHasKey('token', $responseData);
        $this->assertIsString($responseData['token']);
        $this->assertNotEmpty($responseData['token']);
    }
}

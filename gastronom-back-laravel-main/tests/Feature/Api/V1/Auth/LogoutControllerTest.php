<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('api.v1.auth.logout');
    }

    public function test_customer_logout_success(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson($this->route);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Successfully logged out',
                ],
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $customer->id,
            'tokenable_type' => Customer::class,
        ]);
    }

    public function test_customer_logout_unauthenticated(): void
    {
        $response = $this->deleteJson($this->route);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
                'success',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test logout with invalid token
     */
    public function test_customer_logout_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->deleteJson($this->route);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_customer_logout_expired_token(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('auth_token')->plainTextToken;

        $customer->tokens()->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson($this->route);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_customer_logout_multiple_attempts(): void
    {
        $customer = Customer::factory()->create();

        $token = $customer->createToken('auth_token')->plainTextToken;

        $firstResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson($this->route);

        $firstResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Successfully logged out',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $secondResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson($this->route);

        $secondResponse->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_customer_logout_malformed_authorization(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token',
        ])->deleteJson($this->route);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_customer_logout_empty_authorization(): void
    {
        $response = $this->withHeaders([
            'Authorization' => '',
        ])->deleteJson($this->route);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_customer_logout_deletes_only_current_token(): void
    {
        $customer = Customer::factory()->create();

        $token1 = $customer->createToken('auth_token_1')->plainTextToken;
        $token2 = $customer->createToken('auth_token_2')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token1,
        ])->deleteJson($this->route);

        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'auth_token_1',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'auth_token_2',
        ]);

        $testResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token2,
        ])->getJson('/api/v1/home/categories');

        $testResponse->assertStatus(200);
    }
}

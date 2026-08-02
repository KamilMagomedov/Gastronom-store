<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Customer;
use Ichtrojan\Otp\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NewPasswordControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_new_password_success(): void
    {
        // Create customer
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        // Create valid OTP record
        Otp::query()->create([
            'identifier' => 'test@example.com',
            'token' => '1234',
            'expires_at' => now()->addMinutes(30),
            'validity' => 1,
        ]);

        $newPasswordData = [
            'email' => 'test@example.com',
            'token' => '1234',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];
        $response = $this->postJson(route('api.v1.auth.new-password', $newPasswordData));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('newpassword123', $customer->fresh()->password));
    }

    public function test_new_password_invalid_email(): void
    {
        $newPasswordData = [
            'email' => 'nonexistent@example.com',
            'token' => '1234',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson(route('api.v1.auth.new-password', $newPasswordData));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_new_password_invalid_otp(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        Otp::query()->create([
            'identifier' => 'test@example.com',
            'token' => '123456',
            'expires_at' => now()->subMinutes(1),
            'validity' => 1,
        ]);

        $newPasswordData = [
            'email' => 'test@example.com',
            'token' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson(route('api.v1.auth.new-password', $newPasswordData));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_new_password_validation_failure(): void
    {
        $invalidData = [
            'email' => 'invalid-email',
            'token' => '12',
            'password' => '123',
            'password_confirmation' => '456',
        ];

        $response = $this->postJson(route('api.v1.auth.new-password', $invalidData));

        $response->assertStatus(422);
    }

    public function test_new_password_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/new-password', []);

        $response->assertStatus(422);
    }
}

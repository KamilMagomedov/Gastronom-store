<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test getting customer profile successfully
     */
    public function test_show_customer_profile_success(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+79281234567',
            'delivery_street' => '123 Main St',
            'delivery_city' => 'New York',
            'delivery_apartment' => '4A',
            'delivery_postal_code' => '10001',
            'delivery_building' => 'Building A',
            'delivery_entrance' => 'Main',
            'delivery_floor' => '5',
        ]);

        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->getJson(route('api.v1.profile.show'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'phone',
                    'delivery_street',
                    'delivery_city',
                    'delivery_apartment',
                    'delivery_postal_code',
                    'delivery_building',
                    'delivery_entrance',
                    'delivery_floor',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => '+79281234567',
                    'delivery_street' => '123 Main St',
                    'delivery_city' => 'New York',
                    'delivery_apartment' => '4A',
                    'delivery_postal_code' => '10001',
                    'delivery_building' => 'Building A',
                    'delivery_entrance' => 'Main',
                    'delivery_floor' => '5',
                ],
            ]);
    }

    /**
     * Test getting customer profile requires authentication
     */
    public function test_show_customer_profile_requires_authentication(): void
    {
        $response = $this->getJson(route('api.v1.profile.show'));

        $response->assertStatus(401);
    }

    /**
     * Test updating customer profile successfully
     */
    public function test_update_customer_profile_success(): void
    {
        $customer = Customer::factory()->create();

        Sanctum::actingAs($customer, guard: 'customers');

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '+79287654321',
            'delivery_street' => '456 Updated St',
            'delivery_city' => 'Los Angeles',
            'delivery_apartment' => '7B',
            'delivery_postal_code' => '90001',
            'delivery_building' => 'Building B',
            'delivery_entrance' => 'Side',
            'delivery_floor' => '3',
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'phone',
                    'delivery_street',
                    'delivery_city',
                    'delivery_apartment',
                    'delivery_postal_code',
                    'delivery_building',
                    'delivery_entrance',
                    'delivery_floor',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => 'Updated Name',
                    'email' => $customer->email,
                    'phone' => '+79287654321',
                    'delivery_street' => '456 Updated St',
                    'delivery_city' => 'Los Angeles',
                    'delivery_apartment' => '7B',
                    'delivery_postal_code' => '90001',
                    'delivery_building' => 'Building B',
                    'delivery_entrance' => 'Side',
                    'delivery_floor' => '3',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '+79287654321',
            'delivery_street' => '456 Updated St',
            'delivery_city' => 'Los Angeles',
            'delivery_apartment' => '7B',
            'delivery_postal_code' => '90001',
            'delivery_building' => 'Building B',
            'delivery_entrance' => 'Side',
            'delivery_floor' => '3',
        ]);
    }

    /**
     * Test updating customer email successfully
     */
    public function test_update_customer_email_success(): void
    {
        $customer = Customer::factory()->create();

        Sanctum::actingAs($customer, guard: 'customers');

        $updateData = [
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * Test updating customer password successfully
     */
    public function test_update_customer_password_success(): void
    {
        $customer = Customer::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        Sanctum::actingAs($customer, guard: 'customers');

        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue(Hash::check('newpassword123', $customer->fresh()->password));
        $this->assertFalse(Hash::check('oldpassword', $customer->fresh()->password));
    }

    /**
     * Test updating customer profile with duplicate email fails
     */
    public function test_update_customer_profile_duplicate_email_fails(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        Sanctum::actingAs($customer1, guard: 'customers');

        $updateData = [
            'email' => $customer2->email,
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test updating customer profile validation failures
     */
    public function test_update_customer_profile_validation_failures(): void
    {
        $customer = Customer::factory()->create();

        Sanctum::actingAs($customer, guard: 'customers');

        $response = $this->putJson(route('api.v1.profile.update'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'phone' => '+1234567890', // Invalid phone number
            'delivery_street' => str_repeat('a', 256), // Too long
            'delivery_city' => str_repeat('a', 101), // Too long
            'delivery_apartment' => str_repeat('a', 11), // Too long
            'delivery_postal_code' => str_repeat('a', 21), // Too long
            'delivery_building' => str_repeat('a', 11), // Too long
            'delivery_entrance' => str_repeat('a', 11), // Too long
            'delivery_floor' => str_repeat('a', 11), // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'phone',
                'delivery_street',
                'delivery_city',
                'delivery_apartment',
                'delivery_postal_code',
                'delivery_building',
                'delivery_entrance',
                'delivery_floor',
            ]);
    }

    /**
     * Test updating customer profile with phone number without plus sign
     */
    public function test_update_customer_profile_phone_without_plus_success(): void
    {
        $customer = Customer::factory()->create();

        Sanctum::actingAs($customer, guard: 'customers');

        $updateData = [
            'phone' => '79287654321', // Without plus sign
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'phone' => '79287654321',
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '79287654321',
        ]);
    }

    /**
     * Test updating customer profile with invalid phone number fails
     */
    public function test_update_customer_profile_invalid_phone_fails(): void
    {
        $customer = Customer::factory()->create();

        Sanctum::actingAs($customer, guard: 'customers');

        $updateData = [
            'phone' => '+1234567890', // Invalid phone number
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test updating customer profile requires authentication
     */
    public function test_update_customer_profile_requires_authentication(): void
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson(route('api.v1.profile.update'), $updateData);

        $response->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $user;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create(['is_active' => true, 'unit' => 'pcs']);
    }

    public function test_user_can_view_approved_reviews_for_product(): void
    {
        $approvedReview = ProductReview::factory()
            ->approved()
            ->create([
                'customer_id' => $this->customer->id,
                'product_id' => $this->product->id,
            ]);

        ProductReview::factory()
            ->approved()
            ->create([
                'customer_id' => Customer::factory()->create()?->id,
                'product_id' => $this->product->id,
            ]);

        $route = route(
            'api.v1.reviews.index',
            $this->product->slug
        );

        $response = $this->getJson($route);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'rating',
                        'comment',
                        'created_at',
                        'customer' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
                'paginator' => [
                    'per_page',
                    'current_page',
                    'last_page',
                    'total',
                    'has_more',
                ],
            ]);

        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $approvedReview->id]);
        $this->assertDatabaseCount('product_reviews', 2);
    }

    public function test_authenticated_user_can_create_review(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $reviewData = [
            'rating' => 5,
            'comment' => 'Excellent product! Highly recommended.',
        ];

        $route = route(
            'api.v1.reviews.store',
            $this->product->slug
        );

        $response = $this->postJson($route, $reviewData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'message',
                    'review' => [
                        'id',
                        'rating',
                        'comment',
                        'is_approved',
                        'customer' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'message' => 'Review submitted successfully. It will be visible after approval.',
                    'review' => [
                        'rating' => 5,
                        'comment' => 'Excellent product! Highly recommended.',
                        'is_approved' => false,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'comment' => 'Excellent product! Highly recommended.',
            'is_approved' => false,
        ]);
    }

    public function test_unauthenticated_customer_cannot_create_review(): void
    {
        $reviewData = [
            'rating' => 5,
            'comment' => 'Excellent product!',

        ];

        $route = route(
            'api.v1.reviews.store',
            $this->product->slug
        );

        $response = $this->postJson($route, $reviewData);

        $response->assertStatus(401);
    }

    public function test_customer_cannot_review_same_product_twice(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $productReview = ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'comment' => 'Some comment',
        ]);

        $reviewData = [
            'rating' => 4,
            'comment' => 'Another review',
        ];

        $route = route(
            'api.v1.reviews.store',
            $this->product->slug
        );

        $response = $this->postJson($route, $reviewData);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You have already reviewed this product.',
                'data' => [
                    'review' => [
                        'id' => $productReview->id,
                        'rating' => $productReview->rating,
                        'comment' => $productReview->comment,
                        'created_at' => $productReview->created_at->format('Y-m-d H:i:s'),
                        'customer' => [
                            'id' => $this->customer->id,
                            'name' => $this->customer->name,
                        ],
                    ],
                ],
            ]);
    }

    public function test_review_validation(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $reviewData = [
            'rating' => 6,
            'comment' => '',
        ];

        $route = route(
            'api.v1.reviews.store',
            $this->product->slug
        );

        $response = $this->postJson($route, $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_user_can_view_own_review_even_if_not_approved(): void
    {
        $review = ProductReview::factory()
            ->pending()
            ->create([
                'product_id' => $this->product->id,
                'customer_id' => $this->customer->id,
            ]);

        $route = route(
            'api.v1.reviews.index',
            $this->product->slug
        );

        $response = $this->getJson($route);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'is_approved' => false,
        ]);
    }

    public function test_user_can_update_own_review(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $review = ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'comment' => 'Some comment',
            'rating' => 1,
        ]);

        $updateData = [
            'rating' => 4,
            'comment' => 'Updated review text',
        ];

        $route = route(
            'api.v1.reviews.update',
            [
                $this->product->slug,
                $review->id,
            ]
        );

        $response = $this->putJson($route, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Review updated successfully.',
                    'review' => [
                        'rating' => 4,
                        'comment' => 'Updated review text',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'Updated review text',
        ]);
    }

    public function test_user_cannot_update_others_review(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $otherCustomer = Customer::factory()->create();

        $review = ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $updateData = [
            'rating' => 4,
            'comment' => 'Updated review text',
        ];

        $route = route(
            'api.v1.reviews.update',
            [
                $this->product->slug,
                $review->id,
            ]
        );

        $response = $this->putJson($route, $updateData);

        $response->assertStatus(401);
    }

    public function test_user_can_delete_own_review(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $review = ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
        ]);

        $route = route(
            'api.v1.reviews.destroy',
            [
                $this->product->slug, $review->id,
            ]
        );

        $response = $this->deleteJson($route);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Review deleted successfully.',
                ],
            ]);

        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }

    public function test_user_cannot_delete_others_review(): void
    {
        Sanctum::actingAs($this->customer, guard: 'customers');

        $otherCustomer = Customer::factory()->create();

        $review = ProductReview::factory()->create([
            'product_id' => $this->product->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $route = route(
            'api.v1.reviews.destroy',
            [
                $this->product->slug, $review->id,
            ]
        );

        $response = $this->deleteJson($route);

        $response->assertStatus(401);
        $this->assertDatabaseHas('product_reviews', ['id' => $review->id]);
    }
}

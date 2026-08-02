<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ProductReviewCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductReviewRequest;
use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Repositories\ProductReviewRepository;
use App\Services\ProductReviewService;
use App\Support\ApiResponse;

class ProductReviewController extends Controller
{
    /**
     * Display approved reviews for a specific product.
     */
    public function index(Product $product)
    {
        $reviews = $product->approvedReviews()
            ->with('customer:id,name')
            ->latest()
            ->paginate(10);

        $paginator = $reviews->through(fn (ProductReview $productReview) => ProductReviewResource::make($productReview));

        return ApiResponse::paginator($paginator);
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(
        ProductReviewRequest $request,
        ProductReviewRepository $productReviewRepository,
        ProductReviewService $productReviewService,
        Product $product,
    ) {
        $customer = $request->user();

        $existingReview = $productReviewRepository->findReviewForCustomer(
            $product->id,
            $customer->id
        );

        if ($existingReview) {
            return ApiResponse::unprocessableEntity(
                'You have already reviewed this product.',
                ['review' => ProductReviewResource::make($existingReview)]
            );
        }

        $review = $productReviewService->create(
            $product->id,
            $customer->id,
            $request->validated('rating'),
            $request->validated('comment'),
        );

        ProductReviewCreated::dispatch($review);

        return ApiResponse::created([
            'message' => 'Review submitted successfully. It will be visible after approval.',
            'review' => $review->load('customer:id,name'),
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(ProductReviewRequest $request, Product $product, ProductReview $review)
    {
        if ($review->customer_id !== auth()->id()) {
            return ApiResponse::unauthorized('Unauthorized.');
        }

        $review->update($request->validated());

        $review->load('customer:id,name');

        return ApiResponse::success([
            'message' => 'Review updated successfully.',
            'review' => ProductReviewResource::make($review),
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(ProductReviewService $productReviewService, Product $product, ProductReview $review)
    {
        if ($review->customer_id !== auth()->id()) {
            return ApiResponse::unauthorized('Unauthorized.');
        }

        $productReviewService->delete($review);

        return ApiResponse::success([
            'message' => 'Review deleted successfully.',
        ]);
    }
}

<?php

namespace App\Services;

use App\Events\ProductReviewDeleted;
use App\Models\ProductReview;

class ProductReviewService
{
    public function create(
        int $productId,
        int $customerId,
        int $rating,
        string $comment
    ): ProductReview {
        return ProductReview::create([
            'product_id' => $productId,
            'customer_id' => $customerId,
            'rating' => $rating,
            'comment' => $comment,
            'is_approved' => false,
        ]);
    }

    public function delete(ProductReview $productReview): void
    {
        $productReview->delete();

        ProductReviewDeleted::dispatch($productReview);
    }
}

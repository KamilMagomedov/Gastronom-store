<?php

namespace App\Repositories;

use App\Models\ProductReview;

class ProductReviewRepository extends BaseRepository
{
    public function __construct(ProductReview $model)
    {
        parent::__construct($model);
    }

    public function findReviewForCustomer(int $productId, int $customerId): ?ProductReview
    {
        return $this->newQuery()
            ->where('product_id', $productId)
            ->where('customer_id', $customerId)
            ->first();
    }
}

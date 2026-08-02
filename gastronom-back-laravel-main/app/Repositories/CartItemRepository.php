<?php

namespace App\Repositories;

use App\Models\CartItem;

class CartItemRepository extends BaseRepository
{
    public function __construct(CartItem $model)
    {
        parent::__construct($model);
    }

    public function findByProductId(int $productId, array $with = []): ?CartItem
    {
        return $this->newQuery()
            ->with($with)
            ->where('product_id', $productId)
            ->first();
    }
}

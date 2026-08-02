<?php

namespace App\Repositories;

use App\Models\ProductSale;
use Illuminate\Support\Collection;

class ProductSaleRepository extends BaseRepository
{
    public function __construct(ProductSale $model)
    {
        parent::__construct($model);
    }

    public function frequentlyPurchased(): Collection
    {
        return $this->newQuery()
            ->whereHas('product', fn ($q) => $q->active())
            ->with('product.category')
            ->limit(10)
            ->orderByDesc('total_quantity')
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function popularByRevenue(): Collection
    {
        return $this->newQuery()
            ->whereHas('product', fn ($q) => $q->active())
            ->with('product.category')
            ->limit(10)
            ->orderByDesc('total_revenue')
            ->get()
            ->pluck('product')
            ->filter();
    }
}

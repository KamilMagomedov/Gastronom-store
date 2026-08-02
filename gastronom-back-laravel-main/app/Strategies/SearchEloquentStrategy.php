<?php

namespace App\Strategies;

use App\Contracts\StrategySearchContract;
use App\DTO\SearchQuery;
use App\Enums\SearchType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchEloquentStrategy implements StrategySearchContract
{
    public function search(SearchQuery $query): LengthAwarePaginator
    {
        /**
         * @var SearchType $type
         */
        $type = $query->type;

        return match ($type->value) {
            'products' => $this->searchProducts($query),
            'categories' => $this->searchCategories($query),
        };
    }

    private function searchProducts(SearchQuery $query): LengthAwarePaginator
    {
        $productQuery = Product::query()
            ->with(['category', 'media'])
            ->active()
            ->when($query->categoryId, fn (Builder $q) => $q->where('category_id', $query->categoryId))
            ->when($query->priceFrom, fn (Builder $q) => $q->where('price', '>=', $query->priceFrom))
            ->when($query->priceTo, fn (Builder $q) => $q->where('price', '<=', $query->priceTo))
            ->when(
                ! is_null($query->inStock),
                fn (Builder $q) => $q->where('in_stock', $query->inStock)
            );

        if (! empty($query->text)) {
            $productQuery->where(function (Builder $q) use ($query) {
                $q->whereLike('name', "%{$query->text}%")
                    ->orWhereLike('sku', "%{$query->text}%");
            });
        }

        if (empty($query->sort)) {
            $productQuery->orderBy('name');
        } else {
            $allowedSorts = ['price', 'name'];

            foreach ($query->sort as $field => $direction) {
                /**
                 * @var string $field
                 * @var string $direction
                 */
                if (in_array($field, $allowedSorts, true)) {
                    $productQuery->orderBy($field, $direction);
                }
            }
        }

        return $productQuery->paginate(perPage: $query->limit, page: $query->page);
    }

    private function searchCategories(SearchQuery $query): LengthAwarePaginator
    {
        $queryString = $query->text;

        $categoryQuery = Category::query()
            ->with(['media'])
            ->active();

        if (! empty($queryString)) {
            $categoryQuery->where(function (Builder $q) use ($queryString) {
                $q->where('name', 'LIKE', "%{$queryString}%")
                    ->orWhere('description', 'LIKE', "%{$queryString}%");
            });
        }

        return $categoryQuery
            ->orderBy('name')
            ->paginate(perPage: $query->limit, page: $query->page);
    }
}

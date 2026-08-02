<?php

namespace App\Http\Requests\Api\V1;

use App\DTO\SearchQuery;
use App\Enums\SearchType;
use Illuminate\Foundation\Http\FormRequest;

class SearchProductsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'max:50'],
            'category' => ['nullable', 'exists:categories,id'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'in_stock' => ['nullable', 'bool'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'sort' => ['sometimes', 'array'],
            'sort.*' => ['in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'Search query may not be greater than 50 characters.',
            'category.exists' => 'Selected category is invalid.',
            'price_from.numeric' => 'Price from must be a number.',
            'price_from.min' => 'Price from must be at least 0.',
            'price_to.numeric' => 'Price to must be a number.',
            'price_to.min' => 'Price to must be at least 0.',
            'page.integer' => 'Page must be an integer.',
            'page.min' => 'Page must be at least 1.',
            'in_stock.bool' => 'In stock must be true or false.',
            'limit.integer' => 'Limit must be an integer.',
            'limit.min' => 'Limit must be at least 1.',
            'sort.array' => 'Sort must be an array.',
            'sort.*.in' => 'Sort direction must be asc or desc.',
        ];
    }

    public function getDto(): SearchQuery
    {
        return new SearchQuery(
            $this->validated('query'),
            SearchType::PRODUCTS,
            $this->validated('in_stock'),
            $this->validated('page', 1),
            $this->validated('limit', 10),
            $this->validated('category'),
            $this->validated('price_from'),
            $this->validated('price_to'),
            $this->validated('sort', [])
        );
    }
}

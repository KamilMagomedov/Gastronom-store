<?php

namespace App\DTO;

use App\Enums\SearchType;

final class SearchQuery
{
    public function __construct(
        public readonly ?string $text,
        public readonly SearchType $type,
        public readonly ?bool $inStock,
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly ?int $categoryId = null,
        public readonly ?int $priceFrom = null,
        public readonly ?int $priceTo = null,
        public readonly array $sort = [],
    ) {}
}

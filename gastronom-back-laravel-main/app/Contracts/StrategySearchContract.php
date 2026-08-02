<?php

namespace App\Contracts;

use App\DTO\SearchQuery;
use Illuminate\Pagination\LengthAwarePaginator;

interface StrategySearchContract
{
    public function search(SearchQuery $query): LengthAwarePaginator;
}

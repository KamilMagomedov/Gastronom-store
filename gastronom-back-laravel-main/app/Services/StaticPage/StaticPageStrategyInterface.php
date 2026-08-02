<?php

namespace App\Services\StaticPage;

use App\Models\StaticPage;

interface StaticPageStrategyInterface
{
    public function getPage(string $slug): ?StaticPage;

    public function exists(string $slug): bool;
}

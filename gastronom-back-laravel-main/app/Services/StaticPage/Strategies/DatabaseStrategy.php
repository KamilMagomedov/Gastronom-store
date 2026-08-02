<?php

namespace App\Services\StaticPage\Strategies;

use App\Models\StaticPage;
use App\Services\StaticPage\StaticPageStrategyInterface;

class DatabaseStrategy implements StaticPageStrategyInterface
{
    public function getPage(string $slug): ?StaticPage
    {
        return StaticPage::active()
            ->bySlug($slug)
            ->with('sections.requirements')
            ->first();
    }

    public function exists(string $slug): bool
    {
        return StaticPage::active()
            ->bySlug($slug)
            ->exists();
    }
}

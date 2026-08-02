<?php

namespace App\Services\StaticPage;

use App\Models\StaticPage;
use App\Services\StaticPage\Strategies\DatabaseStrategy;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StaticPageService
{
    private DatabaseStrategy $strategy;

    public function __construct(DatabaseStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function getPage(string $slug): StaticPage
    {
        return $this->strategy
            ->getPage($slug)
            ?? throw new ModelNotFoundException;
    }
}

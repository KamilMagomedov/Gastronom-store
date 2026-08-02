<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function findByExternalId(string $externalId): ?Category
    {
        return $this->firstWhere('external_id', $externalId);
    }

    public function createIfNotExists(array $data): Category
    {
        $category = $this->findByExternalId($data['external_id']);

        if (! $category) {
            $category = $this->create($data);
        }

        return $category;
    }

    public function getActiveForHome(): Collection
    {
        return $this->newQuery()
            ->notSystem()
            ->active()
            ->orderBy('name')
            ->get();
    }
}

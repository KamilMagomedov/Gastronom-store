<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findByExternalId(string $externalId): ?Product
    {
        return $this->firstWhere('external_id', $externalId);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->firstWhere('slug', $slug);
    }

    public function findBySlugAndNotId(string $slug, ?int $excludeId): ?Product
    {
        $query = $this->newQuery()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function getIdByExternalId(string $externalId): ?int
    {
        return $this->findByExternalId($externalId)?->id;
    }
}

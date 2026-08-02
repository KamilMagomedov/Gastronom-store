<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    public function find($id, $with = [], $columns = ['*']): ?Model
    {
        return $this
            ->newQuery()
            ->with($with)
            ->find($id, $columns);
    }

    public function firstWhere(
        string $column,
        mixed $value,
        array $with = [],
        array $columns = ['*']
    ): ?Model {
        return $this
            ->newQuery()
            ->with($with)
            ->select($columns)
            ->where($column, $value)
            ->first($columns);
    }
}

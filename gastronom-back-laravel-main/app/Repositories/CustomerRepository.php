<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->newQuery()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
    }
}

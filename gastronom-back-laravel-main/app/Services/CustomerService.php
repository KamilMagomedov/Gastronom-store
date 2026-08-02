<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function create(string $name, string $email, string $password): Customer
    {
        return Customer::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }
}

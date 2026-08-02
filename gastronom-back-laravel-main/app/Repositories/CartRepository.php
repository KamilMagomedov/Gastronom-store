<?php

namespace App\Repositories;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class CartRepository extends BaseRepository
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    public function getCartForCustomer(array $with = ['items']): ?Cart
    {
        return $this->newQuery()
            ->active()
            ->forCustomer(Auth::guard('customers')->id())
            ->with($with)
            ->first();
    }

    public function getCartForGuest(?string $sessionId, array $items = ['items']): ?Cart
    {
        if (is_null($sessionId)) {
            return null;
        }

        return $this->newQuery()
            ->active()
            ->forSession($sessionId)
            ->with($items)
            ->first();
    }
}

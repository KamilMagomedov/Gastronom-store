<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id, array $with = []): ?Order
    {
        return $this->newQuery()
            ->with($with)
            ->find($id);
    }

    public function findByCustomer(int $customerId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->newQuery()->with(['customer', 'orderItems.product'])
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function create(array $data): Order
    {
        return $this->newQuery()->create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh();
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function canBeCancelled(Order $order): bool
    {
        return in_array($order->status, [
            'pending',
            'confirmed',
            'preparing',
        ]);
    }

    public function createOrderItem(Order $order, array $data)
    {
        return $order->orderItems()->create($data);
    }
}

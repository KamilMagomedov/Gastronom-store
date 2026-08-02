<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('order-owner', function ($user, Order $order) {
            return $user->id === $order->customer_id;
        });
    }
}

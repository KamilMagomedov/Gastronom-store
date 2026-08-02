<?php

namespace App\Providers;

use App\Enums\SearchStrategyType;
use App\Strategies\SearchStrategyManager;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SearchStrategyManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerSearchStrategies();
    }

    public function registerSearchStrategies(): void
    {
        $strategyManager = $this->app->make(SearchStrategyManager::class);

        foreach (SearchStrategyType::cases() as $strategyType) {
            $strategyManager->register(
                $strategyType->value,
                $strategyType->strategy()
            );
        }
    }
}

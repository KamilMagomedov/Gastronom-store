<?php

namespace App\Strategies;

use App\Contracts\StrategySearchContract;
use App\Enums\SearchStrategyType;
use Illuminate\Contracts\Container\Container;

class SearchStrategyManager
{
    protected array $strategies = [];

    public function __construct(public Container $container) {}

    public function resolve(string|SearchStrategyType $name): StrategySearchContract
    {
        $strategyName = $name?->value ?? $name;

        if (! array_key_exists($strategyName, $this->strategies)) {
            throw new \Exception("Strategy \"$strategyName\" not found");
        }

        return $this->container->make($this->strategies[$strategyName]);
    }

    public function register(string $name, string $className): void
    {
        if (! is_subclass_of($className, StrategySearchContract::class)) {
            throw new \InvalidArgumentException(
                "$className must implement StrategySearchContract"
            );
        }

        $this->strategies[$name] = $className;
    }

    public function getEloquentStrategy(): StrategySearchContract
    {
        return $this->resolve(SearchStrategyType::ELOQUENT);
    }
}

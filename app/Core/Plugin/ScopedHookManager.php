<?php

declare(strict_types=1);

namespace App\Core\Plugin;

final class ScopedHookManager extends HookManager
{
    public function __construct(
        private readonly HookManager $inner,
        private readonly string $scope,
    ) {
    }

    public function register(string $hook, callable $callback, int $priority = 10, string $scope = 'global'): void
    {
        $this->inner->register($hook, $callback, $priority, $this->scope);
    }

    /**
     * @return array<int, mixed>
     */
    public function fire(string $hook, mixed ...$args): array
    {
        return $this->inner->fire($hook, ...$args);
    }

    public function filter(string $hook, callable $callback, int $priority = 10, string $scope = 'global'): void
    {
        $this->inner->filter($hook, $callback, $priority, $this->scope);
    }

    public function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return $this->inner->applyFilter($hook, $value, ...$args);
    }

    public function render(string $hook, mixed ...$args): string
    {
        return $this->inner->render($hook, ...$args);
    }

    public function forgetScope(string $scope): void
    {
        $this->inner->forgetScope($scope);
    }
}

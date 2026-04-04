<?php

declare(strict_types=1);

namespace App\Core\Plugin;

use Stringable;

class HookManager
{
    /**
     * @var array<string, array<int, array{priority:int, callback:callable, scope:string}>>
     */
    private array $actions = [];

    /**
     * @var array<string, array<int, array{priority:int, callback:callable, scope:string}>>
     */
    private array $filters = [];

    public function register(string $hook, callable $callback, int $priority = 10, string $scope = 'global'): void
    {
        $this->actions[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
            'scope' => $scope,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function fire(string $hook, mixed ...$args): array
    {
        $results = [];

        foreach ($this->sortedCallbacks($this->actions[$hook] ?? []) as $listener) {
            $results[] = ($listener['callback'])(...$args);
        }

        return $results;
    }

    public function filter(string $hook, callable $callback, int $priority = 10, string $scope = 'global'): void
    {
        $this->filters[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
            'scope' => $scope,
        ];
    }

    public function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->sortedCallbacks($this->filters[$hook] ?? []) as $listener) {
            $value = ($listener['callback'])($value, ...$args);
        }

        return $value;
    }

    public function render(string $hook, mixed ...$args): string
    {
        $output = '';

        foreach ($this->fire($hook, ...$args) as $result) {
            if (is_string($result) || $result instanceof Stringable) {
                $output .= (string) $result;
            }
        }

        return $output;
    }

    /**
     * Remove every listener registered under a given scope.
     */
    public function forgetScope(string $scope): void
    {
        foreach ($this->actions as $hook => $listeners) {
            $this->actions[$hook] = array_values(array_filter(
                $listeners,
                static fn (array $listener): bool => $listener['scope'] !== $scope,
            ));

            if ($this->actions[$hook] === []) {
                unset($this->actions[$hook]);
            }
        }

        foreach ($this->filters as $hook => $listeners) {
            $this->filters[$hook] = array_values(array_filter(
                $listeners,
                static fn (array $listener): bool => $listener['scope'] !== $scope,
            ));

            if ($this->filters[$hook] === []) {
                unset($this->filters[$hook]);
            }
        }
    }

    /**
     * @param  array<int, array{priority:int, callback:callable, scope:string}>  $callbacks
     * @return array<int, array{priority:int, callback:callable, scope:string}>
     */
    private function sortedCallbacks(array $callbacks): array
    {
        usort($callbacks, static fn (array $left, array $right): int => $left['priority'] <=> $right['priority']);

        return $callbacks;
    }
}

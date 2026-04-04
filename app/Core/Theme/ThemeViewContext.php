<?php

declare(strict_types=1);

namespace App\Core\Theme;

class ThemeViewContext
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function replace(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function merge(array $data): void
    {
        $this->data = array_replace_recursive($this->data, $data);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $this->data;
        }

        return data_get($this->data, $key, $default);
    }

    public function has(string $key): bool
    {
        return data_get($this->data, $key, '__missing__') !== '__missing__';
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function flush(): void
    {
        $this->data = [];
    }
}

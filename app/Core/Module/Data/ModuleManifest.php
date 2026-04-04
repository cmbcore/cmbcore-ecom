<?php

declare(strict_types=1);

namespace App\Core\Module\Data;

use App\Core\Module\Contracts\ModuleInterface;
use InvalidArgumentException;

final class ModuleManifest implements ModuleInterface
{
    /**
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $providers
     * @param  array<int, array<string, mixed>>  $adminMenu
     * @param  array<string, string>  $adminPages
     * @param  array<int, string>  $fires
     * @param  array<int, string>  $listens
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $nameKey,
        private readonly string $alias,
        private readonly string $version,
        private readonly string $description,
        private readonly ?string $descriptionKey,
        private readonly int $order,
        private readonly bool $enabled,
        private readonly bool $manifestEnabled,
        private readonly array $dependencies,
        private readonly array $providers,
        private readonly array $adminMenu,
        private readonly array $adminPages,
        private readonly array $fires,
        private readonly array $listens,
        private readonly string $path,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        foreach (['name', 'alias', 'version', 'enabled', 'providers'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException("Module manifest at [{$path}] is missing required field [{$field}].");
            }
        }

        $enabled = (bool) $data['enabled'];

        return new self(
            name: (string) $data['name'],
            nameKey: isset($data['name_key']) ? (string) $data['name_key'] : null,
            alias: (string) $data['alias'],
            version: (string) $data['version'],
            description: (string) ($data['description'] ?? ''),
            descriptionKey: isset($data['description_key']) ? (string) $data['description_key'] : null,
            order: (int) ($data['order'] ?? 100),
            enabled: $enabled,
            manifestEnabled: $enabled,
            dependencies: array_values($data['dependencies'] ?? []),
            providers: array_values($data['providers'] ?? []),
            adminMenu: array_values(data_get($data, 'admin.menu', [])),
            adminPages: data_get($data, 'admin.pages', []),
            fires: array_values(data_get($data, 'hooks.fires', [])),
            listens: array_values(data_get($data, 'hooks.listens', [])),
            path: $path,
        );
    }

    public function withEnabled(bool $enabled): self
    {
        return new self(
            name: $this->name,
            nameKey: $this->nameKey,
            alias: $this->alias,
            version: $this->version,
            description: $this->description,
            descriptionKey: $this->descriptionKey,
            order: $this->order,
            enabled: $enabled,
            manifestEnabled: $this->manifestEnabled,
            dependencies: $this->dependencies,
            providers: $this->providers,
            adminMenu: $this->adminMenu,
            adminPages: $this->adminPages,
            fires: $this->fires,
            listens: $this->listens,
            path: $this->path,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getNameKey(): ?string
    {
        return $this->nameKey;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDescriptionKey(): ?string
    {
        return $this->descriptionKey;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isManifestEnabled(): bool
    {
        return $this->manifestEnabled;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getAdminMenu(): array
    {
        return $this->adminMenu;
    }

    public function getAdminPages(): array
    {
        return $this->adminPages;
    }

    public function getFires(): array
    {
        return $this->fires;
    }

    public function getListens(): array
    {
        return $this->listens;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'name_key' => $this->nameKey,
            'alias' => $this->alias,
            'version' => $this->version,
            'description' => $this->description,
            'description_key' => $this->descriptionKey,
            'order' => $this->order,
            'enabled' => $this->enabled,
            'manifest_enabled' => $this->manifestEnabled,
            'dependencies' => $this->dependencies,
            'providers' => $this->providers,
            'admin' => [
                'menu' => $this->adminMenu,
                'pages' => $this->adminPages,
            ],
            'hooks' => [
                'fires' => $this->fires,
                'listens' => $this->listens,
            ],
            'path' => $this->path,
        ];
    }
}

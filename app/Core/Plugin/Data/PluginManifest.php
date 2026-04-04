<?php

declare(strict_types=1);

namespace App\Core\Plugin\Data;

use InvalidArgumentException;
use Illuminate\Support\Str;

final class PluginManifest
{
    /**
     * @param  array<string, mixed>  $requires
     * @param  array<int, array<string, mixed>>  $settings
     * @param  array<int, string>  $listens
     * @param  array<int, array<string, mixed>>  $adminMenu
     * @param  array<string, string>  $adminPages
     * @param  array<string, mixed>  $install
     */
    public function __construct(
        private readonly string $name,
        private readonly string $alias,
        private readonly string $version,
        private readonly string $description,
        private readonly string $author,
        private readonly string $url,
        private readonly array $requires,
        private readonly array $settings,
        private readonly array $listens,
        private readonly array $adminMenu,
        private readonly array $adminPages,
        private readonly array $install,
        private readonly string $mainClass,
        private readonly string $mainFile,
        private readonly string $path,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        foreach (['name', 'alias', 'version'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException("Plugin manifest at [{$path}] is missing required field [{$field}].");
            }
        }

        $classBaseName = (string) ($data['main_class_basename'] ?? Str::studly(basename($path)) . 'Plugin');
        $mainClass = (string) ($data['main_class'] ?? 'Plugins\\' . Str::studly(basename($path)) . '\\' . $classBaseName);
        $mainFile = (string) ($data['main_file'] ?? $path . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $classBaseName . '.php');

        return new self(
            name: (string) $data['name'],
            alias: (string) $data['alias'],
            version: (string) $data['version'],
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            requires: $data['requires'] ?? [],
            settings: array_values($data['settings'] ?? []),
            listens: array_values(data_get($data, 'hooks.listens', [])),
            adminMenu: array_values(data_get($data, 'admin.menu', [])),
            adminPages: data_get($data, 'admin.pages', []),
            install: is_array($data['install'] ?? null) ? $data['install'] : [],
            mainClass: $mainClass,
            mainFile: $mainFile,
            path: $path,
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

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequires(): array
    {
        return $this->requires;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return array<int, string>
     */
    public function getListens(): array
    {
        return $this->listens;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenu(): array
    {
        return $this->adminMenu;
    }

    /**
     * @return array<string, string>
     */
    public function getAdminPages(): array
    {
        return $this->adminPages;
    }

    public function getMainClass(): string
    {
        return $this->mainClass;
    }

    public function getMainFile(): string
    {
        return $this->mainFile;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInstall(): array
    {
        return $this->install;
    }
}

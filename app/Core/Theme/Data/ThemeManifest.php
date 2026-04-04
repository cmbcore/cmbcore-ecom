<?php

declare(strict_types=1);

namespace App\Core\Theme\Data;

use App\Core\Theme\Contracts\ThemeInterface;
use InvalidArgumentException;

final class ThemeManifest implements ThemeInterface
{
    /**
     * @param  array<int, string>  $supports
     * @param  array<int, array<string, mixed>>  $settings
     * @param  array<string, mixed>  $templates
     * @param  array<int, array<string, mixed>>  $menus
     * @param  array<string, mixed>  $install
     */
    public function __construct(
        private readonly string $name,
        private readonly string $alias,
        private readonly string $version,
        private readonly string $description,
        private readonly string $author,
        private readonly ?string $screenshot,
        private readonly ?string $parent,
        private readonly array $supports,
        private readonly array $settings,
        private readonly array $templates,
        private readonly array $menus,
        private readonly array $install,
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
                throw new InvalidArgumentException("Theme manifest at [{$path}] is missing required field [{$field}].");
            }
        }

        return new self(
            name: (string) $data['name'],
            alias: (string) $data['alias'],
            version: (string) $data['version'],
            description: (string) ($data['description'] ?? ''),
            author: (string) ($data['author'] ?? ''),
            screenshot: isset($data['screenshot']) ? (string) $data['screenshot'] : null,
            parent: isset($data['parent']) && $data['parent'] !== null ? (string) $data['parent'] : null,
            supports: array_values($data['supports'] ?? []),
            settings: array_values($data['settings'] ?? []),
            templates: $data['templates'] ?? [],
            menus: array_values($data['menus'] ?? []),
            install: is_array($data['install'] ?? null) ? $data['install'] : [],
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

    public function getScreenshot(): ?string
    {
        return $this->screenshot;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function getSupports(): array
    {
        return $this->supports;
    }

    public function getSettingsSchema(): array
    {
        return $this->settings;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getMenus(): array
    {
        return $this->menus;
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

    /**
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
    {
        $defaults = [];

        foreach ($this->settings as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                if (isset($field['key']) && array_key_exists('default', $field)) {
                    $defaults[(string) $field['key']] = $field['default'];
                }
            }
        }

        return $defaults;
    }
}

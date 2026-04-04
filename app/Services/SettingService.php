<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        if (! $this->tableAvailable()) {
            return $default;
        }

        $setting = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (! $setting instanceof Setting) {
            return $default;
        }

        return $this->castValue($setting, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return Setting::query()
            ->where('group', $group)
            ->orderBy('position')
            ->get()
            ->mapWithKeys(function (Setting $setting): array {
                return [$setting->key => $this->castValue($setting)];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    public function sync(array $definitions): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        foreach ($definitions as $definition) {
            Setting::query()->updateOrCreate(
                [
                    'group' => (string) $definition['group'],
                    'key' => (string) $definition['key'],
                ],
                [
                    'value' => $this->prepareValue($definition['value'] ?? null),
                    'type' => (string) ($definition['type'] ?? 'text'),
                    'label' => (string) ($definition['label'] ?? ''),
                    'description' => $definition['description'] ?? null,
                    'options' => $definition['options'] ?? null,
                    'position' => (int) ($definition['position'] ?? 0),
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(string $group): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return Setting::query()
            ->where('group', $group)
            ->orderBy('position')
            ->get([
                'id',
                'group',
                'key',
                'value',
                'type',
                'label',
                'description',
                'options',
                'position',
            ])
            ->map(function (Setting $setting): array {
                return [
                    'id' => $setting->id,
                    'group' => $setting->group,
                    'key' => $setting->key,
                    'value' => $this->castValue($setting),
                    'type' => $setting->type,
                    'label' => $setting->label,
                    'description' => $setting->description,
                    'options' => $setting->options ?? [],
                    'position' => (int) $setting->position,
                ];
            })
            ->all();
    }

    public function set(
        string $group,
        string $key,
        mixed $value,
        string $type = 'text',
        ?string $label = null,
        ?string $description = null,
        ?array $options = null,
        int $position = 0,
    ): void {
        if (! $this->tableAvailable()) {
            return;
        }

        Setting::query()->updateOrCreate(
            [
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $this->prepareValue($value),
                'type' => $type,
                'label' => $label ?? $key,
                'description' => $description,
                'options' => $options,
                'position' => $position,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveGroup(string $group, array $values): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        $existing = Setting::query()
            ->where('group', $group)
            ->get()
            ->keyBy('key');

        foreach ($values as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            /** @var Setting|null $setting */
            $setting = $existing->get($key);

            $this->set(
                $group,
                $key,
                $value,
                $setting?->type ?? $this->inferType($value),
                $setting?->label ?? $key,
                $setting?->description,
                $setting?->options,
                (int) ($setting?->position ?? 0),
            );
        }
    }

    private function tableAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function castValue(Setting $setting, mixed $default = null): mixed
    {
        if ($setting->value === null) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'number' => is_numeric($setting->value) ? $setting->value + 0 : $default,
            'json' => json_decode((string) $setting->value, true, 512) ?? $default,
            'select' => $setting->value,
            default => $setting->value,
        };
    }

    private function prepareValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        return 'text';
    }
}

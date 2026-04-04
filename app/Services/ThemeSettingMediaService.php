<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ThemeSettingMediaService
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @param  array<string, mixed>  $settings
     * @param  array<string, UploadedFile>  $uploads
     * @return array<string, mixed>
     */
    public function resolve(string $themeAlias, array $groups, array $settings, array $uploads = []): array
    {
        $resolved = [];

        foreach ($groups as $group) {
            foreach ((array) ($group['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');

                if ($key === '') {
                    continue;
                }

                $resolved[$key] = $this->resolveFieldValue(
                    $themeAlias,
                    $field,
                    $settings[$key] ?? ($field['default'] ?? null),
                    $uploads,
                );
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, UploadedFile>  $uploads
     */
    private function resolveFieldValue(string $themeAlias, array $field, mixed $value, array $uploads): mixed
    {
        return match ((string) ($field['type'] ?? 'text')) {
            'image' => $this->resolveImageValue($themeAlias, $value, $uploads),
            'tags' => $this->normalizeTags($value),
            'object' => $this->resolveObjectValue($themeAlias, $field, $value, $uploads),
            'repeater' => $this->resolveRepeaterValue($themeAlias, $field, $value, $uploads),
            default => $value,
        };
    }

    /**
     * @param  array<string, UploadedFile>  $uploads
     */
    private function resolveImageValue(string $themeAlias, mixed $value, array $uploads): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return Storage::disk('public')->url(
                $this->imageUploadService->store($value, "theme-settings/{$themeAlias}"),
            );
        }

        if (is_array($value)) {
            if (($value['remove'] ?? false) === true) {
                return null;
            }

            $uploadToken = (string) ($value['upload_token'] ?? '');

            if ($uploadToken !== '' && isset($uploads[$uploadToken])) {
                return Storage::disk('public')->url(
                    $this->imageUploadService->store($uploads[$uploadToken], "theme-settings/{$themeAlias}"),
                );
            }

            $nextValue = $value['value'] ?? $value['url'] ?? null;

            return $this->resolveImageValue($themeAlias, $nextValue, $uploads);
        }

        $path = trim((string) $value);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, '/theme-assets/rhysman/')) {
            return Str::replaceFirst('/theme-assets/rhysman/', '/theme-assets/cmbcore/', $path);
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $this->downloadRemoteImage($themeAlias, $path);
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, UploadedFile>  $uploads
     * @return array<string, mixed>
     */
    private function resolveObjectValue(string $themeAlias, array $field, mixed $value, array $uploads): array
    {
        $values = is_array($value) ? $value : [];
        $resolved = [];

        foreach ((array) ($field['fields'] ?? []) as $childField) {
            $key = (string) ($childField['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $resolved[$key] = $this->resolveFieldValue(
                $themeAlias,
                $childField,
                $values[$key] ?? ($childField['default'] ?? null),
                $uploads,
            );
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, UploadedFile>  $uploads
     * @return array<int, array<string, mixed>>
     */
    private function resolveRepeaterValue(string $themeAlias, array $field, mixed $value, array $uploads): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];

            foreach ((array) ($field['fields'] ?? []) as $childField) {
                $key = (string) ($childField['key'] ?? '');

                if ($key === '') {
                    continue;
                }

                $row[$key] = $this->resolveFieldValue(
                    $themeAlias,
                    $childField,
                    $item[$key] ?? ($childField['default'] ?? null),
                    $uploads,
                );
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTags(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn (mixed $item): bool => is_scalar($item) && trim((string) $item) !== '')
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->values()
            ->all();
    }

    private function downloadRemoteImage(string $themeAlias, string $url): string
    {
        // Block private/internal IPs to prevent SSRF
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            throw new RuntimeException("Invalid URL [{$url}].");
        }

        $response = Http::timeout(20)->retry(2, 250)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Unable to download theme asset [{$url}].");
        }

        // Validate content type is actually an image
        $contentType = (string) ($response->header('Content-Type') ?? '');

        if ($contentType !== '' && ! str_starts_with($contentType, 'image/')) {
            throw new RuntimeException("Remote URL [{$url}] is not an image (content-type: {$contentType}).");
        }

        // Enforce size limit (10 MB)
        if (strlen($response->body()) > 10 * 1024 * 1024) {
            throw new RuntimeException("Remote image [{$url}] exceeds 10MB size limit.");
        }

        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : 'jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'theme-asset-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to allocate temp file for theme asset download.');
        }

        file_put_contents($tempPath, $response->body());

        $file = new UploadedFile(
            $tempPath,
            'theme-asset.' . $extension,
            $response->header('Content-Type') ?? null,
            null,
            true,
        );

        return Storage::disk('public')->url(
            $this->imageUploadService->store($file, "theme-settings/{$themeAlias}"),
        );
    }
}

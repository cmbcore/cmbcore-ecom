<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class PackageZipInstaller
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @return array{extract_path:string,package_root:string,manifest_path:string,manifest:array<string,mixed>}
     */
    public function unpack(UploadedFile $archive, string $manifestFilename): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is not available.');
        }

        $extractPath = $this->temporaryDirectory();
        $zip = new ZipArchive();
        $opened = $zip->open($archive->getRealPath());

        if ($opened !== true) {
            $this->cleanup($extractPath);

            throw new RuntimeException('Unable to open the uploaded ZIP package.');
        }

        try {
            $this->guardEntries($zip);

            if (! $zip->extractTo($extractPath)) {
                throw new RuntimeException('Unable to extract the uploaded ZIP package.');
            }
        } finally {
            $zip->close();
        }

        $manifestPaths = collect($this->files->allFiles($extractPath))
            ->map(static fn ($file): string => $file->getPathname())
            ->filter(static fn (string $path): bool => basename($path) === $manifestFilename)
            ->values();

        if ($manifestPaths->count() !== 1) {
            $this->cleanup($extractPath);

            throw new RuntimeException("Expected exactly one [{$manifestFilename}] inside the ZIP package.");
        }

        $manifestPath = $manifestPaths->first();

        try {
            /** @var array<string, mixed> $manifest */
            $manifest = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            $this->cleanup($extractPath);

            throw new RuntimeException("Unable to decode [{$manifestFilename}]: {$exception->getMessage()}");
        }

        return [
            'extract_path' => $extractPath,
            'package_root' => dirname($manifestPath),
            'manifest_path' => $manifestPath,
            'manifest' => $manifest,
        ];
    }

    public function cleanup(string $extractPath): void
    {
        if ($this->files->isDirectory($extractPath)) {
            $this->files->deleteDirectory($extractPath);
        }
    }

    private function temporaryDirectory(): string
    {
        $path = storage_path('app/tmp/package-installer-' . bin2hex(random_bytes(8)));

        $this->files->ensureDirectoryExists($path);

        return $path;
    }

    private function guardEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            if (
                str_starts_with($name, '/') ||
                preg_match('/^[A-Za-z]:\//', $name) === 1 ||
                str_contains($name, '../') ||
                str_contains($name, '..\\')
            ) {
                throw new RuntimeException('The uploaded ZIP package contains an unsafe path.');
            }
        }
    }
}

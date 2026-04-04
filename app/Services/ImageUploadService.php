<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\MediaLibrary\Services\MediaLibraryService;

class ImageUploadService
{
    /**
     * Store an uploaded image to the given subdirectory under the public disk.
     *
     * Returns the relative path (e.g. "blog/ABC123.jpg") suitable for
     * storing in the database.  The full public URL can be obtained via
     * Storage::url($path).
     *
     * @param  UploadedFile  $file
     * @param  string  $folder  – subdirectory inside public disk (no leading slash)
     * @return string
     */
    public function store(UploadedFile $file, string $folder = 'uploads'): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::ulid() . '.' . $extension;

        $file->storePubliclyAs($folder, $filename, 'public');
        $path = $folder . '/' . $filename;
        $imageInfo = @getimagesize($file->getRealPath());

        app(MediaLibraryService::class)->recordStoredFile(
            disk: 'public',
            path: $path,
            filename: $file->getClientOriginalName(),
            mimeType: (string) $file->getMimeType(),
            size: (int) ($file->getSize() ?? 0),
            folder: $folder,
            width: is_array($imageInfo) ? ($imageInfo[0] ?? null) : null,
            height: is_array($imageInfo) ? ($imageInfo[1] ?? null) : null,
        );

        return $path;
    }

    /**
     * Delete a previously stored image by its relative path.
     * Safe to call with null (no-op).
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            app(MediaLibraryService::class)->deleteByPath($path);
        }
    }
}

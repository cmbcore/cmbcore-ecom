<?php

declare(strict_types=1);

namespace Modules\MediaLibrary\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\MediaLibrary\Models\MediaLibraryItem;

class MediaLibraryService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return MediaLibraryItem::query()
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (MediaLibraryItem $item): array => [
                'id' => $item->id,
                'filename' => $item->filename,
                'disk' => $item->disk,
                'path' => $item->path,
                'mime_type' => $item->mime_type,
                'size' => (int) $item->size,
                'width' => $item->width,
                'height' => $item->height,
                'alt_text' => $item->alt_text,
                'folder' => $item->folder,
                'url' => Storage::disk($item->disk)->url($item->path),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function upload(UploadedFile $file, string $folder = 'uploads'): array
    {
        $disk = (string) config('media_library.disk', 'public');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = $file->storeAs($folder, $filename, $disk);

        $imageInfo = @getimagesize($file->getRealPath());

        return $this->recordStoredFile(
            disk: $disk,
            path: $path,
            filename: $file->getClientOriginalName(),
            mimeType: (string) $file->getMimeType(),
            size: (int) ($file->getSize() ?? 0),
            folder: $folder,
            width: is_array($imageInfo) ? ($imageInfo[0] ?? null) : null,
            height: is_array($imageInfo) ? ($imageInfo[1] ?? null) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function recordStoredFile(
        string $disk,
        string $path,
        string $filename,
        ?string $mimeType = null,
        int $size = 0,
        ?string $folder = null,
        ?int $width = null,
        ?int $height = null,
        ?string $altText = null,
    ): array {
        /** @var MediaLibraryItem $item */
        $item = MediaLibraryItem::query()->updateOrCreate(
            ['path' => $path],
            [
                'filename' => $filename,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'alt_text' => $altText,
                'folder' => $folder,
            ],
        );

        return [
            'id' => $item->id,
            'filename' => $item->filename,
            'disk' => $item->disk,
            'path' => $item->path,
            'mime_type' => $item->mime_type,
            'size' => (int) $item->size,
            'width' => $item->width,
            'height' => $item->height,
            'alt_text' => $item->alt_text,
            'folder' => $item->folder,
            'url' => Storage::disk($item->disk)->url($item->path),
        ];
    }

    public function delete(int $id): void
    {
        /** @var MediaLibraryItem $item */
        $item = MediaLibraryItem::query()->findOrFail($id);
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
    }

    public function deleteByPath(string $path): void
    {
        MediaLibraryItem::query()->where('path', $path)->delete();
    }
}

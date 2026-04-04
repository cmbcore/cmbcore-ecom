<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\MediaLibrary\Services\MediaLibraryService;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMedia;

class MediaUploadService
{
    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<string, int>  $skuIdMap
     * @return Collection<int, ProductMedia>
     */
    public function sync(Product $product, array $definitions, array $uploads = [], array $skuIdMap = []): Collection
    {
        $definitions = array_values(array_filter($definitions, static fn (mixed $definition): bool => is_array($definition)));
        $uploads = array_values(array_filter($uploads, static fn (mixed $file): bool => $file instanceof UploadedFile));

        $existingMedia = $product->media()->get()->keyBy('id');
        $normalized = [];

        foreach ($definitions as $index => $definition) {
            $normalized[] = $this->normalizeDefinition($product, $existingMedia, $definition, $uploads, $skuIdMap, $index);
        }

        $this->validateMediaLimits($normalized);

        $keepIds = collect($normalized)
            ->pluck('existing_id')
            ->filter()
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        foreach ($existingMedia->except($keepIds) as $media) {
            Storage::disk($media->disk)->delete($media->path);
            app(MediaLibraryService::class)->deleteByPath($media->path);
            $media->delete();
        }

        foreach ($normalized as $position => $item) {
            if ($item['existing'] instanceof ProductMedia) {
                $item['existing']->forceFill([
                    'product_sku_id' => $item['product_sku_id'],
                    'position' => $position,
                    'alt_text' => $item['alt_text'],
                    'metadata' => array_replace($item['existing']->metadata ?? [], $item['metadata']),
                ])->save();

                continue;
            }

            /** @var UploadedFile $file */
            $file = $item['file'];
            $disk = (string) config('product.media.disk', 'public');
            $directory = 'products/' . $product->id . '/' . $item['type'];
            $filename = $this->generateFilename($file);
            $storedPath = $file->storeAs($directory, $filename, $disk);

            ProductMedia::query()->create([
                'product_id' => $product->id,
                'product_sku_id' => $item['product_sku_id'],
                'type' => $item['type'],
                'path' => $storedPath,
                'disk' => $disk,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'size' => $file->getSize(),
                'position' => $position,
                'alt_text' => $item['alt_text'],
                'metadata' => $item['metadata'],
            ]);

            $dimensions = $item['metadata'];
            app(MediaLibraryService::class)->recordStoredFile(
                disk: $disk,
                path: $storedPath,
                filename: $file->getClientOriginalName(),
                mimeType: (string) $file->getMimeType(),
                size: (int) ($file->getSize() ?? 0),
                folder: $directory,
                width: is_numeric($dimensions['width'] ?? null) ? (int) $dimensions['width'] : null,
                height: is_numeric($dimensions['height'] ?? null) ? (int) $dimensions['height'] : null,
                altText: $item['alt_text'],
            );
        }

        return $product->media()->get();
    }

    /**
     * @param  Collection<int, ProductMedia>  $existingMedia
     * @param  array<string, mixed>  $definition
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<string, int>  $skuIdMap
     * @return array<string, mixed>
     */
    private function normalizeDefinition(
        Product $product,
        Collection $existingMedia,
        array $definition,
        array $uploads,
        array $skuIdMap,
        int $position,
    ): array {
        $existingId = isset($definition['id']) ? (int) $definition['id'] : null;
        $existing = $existingId ? $existingMedia->get($existingId) : null;

        if ($existingId !== null && ! $existing instanceof ProductMedia) {
            throw ValidationException::withMessages([
                'media' => [__('admin.products.validation.media_not_found')],
            ]);
        }

        $productSkuId = $this->resolveSkuId($product, $definition, $skuIdMap);
        $altText = trim((string) ($definition['alt_text'] ?? ''));
        $resizeSettings = $this->normalizeResizeSettings($definition['resize_settings'] ?? []);

        if ($existing instanceof ProductMedia) {
            return [
                'existing_id' => $existing->id,
                'existing' => $existing,
                'file' => null,
                'type' => $existing->type,
                'product_sku_id' => $productSkuId,
                'alt_text' => $altText,
                'metadata' => $resizeSettings !== [] ? ['resize_settings' => $resizeSettings] : [],
                'position' => $position,
            ];
        }

        $uploadIndex = isset($definition['upload_index']) ? (int) $definition['upload_index'] : null;
        $file = $uploadIndex !== null ? ($uploads[$uploadIndex] ?? null) : null;

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'media' => [__('admin.products.validation.media_file_missing')],
            ]);
        }

        $type = $this->resolveType($file);

        return [
            'existing_id' => null,
            'existing' => null,
            'file' => $file,
            'type' => $type,
            'product_sku_id' => $productSkuId,
            'alt_text' => $altText,
            'metadata' => array_replace(
                $this->collectMetadata($file, $type),
                $resizeSettings !== [] ? ['resize_settings' => $resizeSettings] : [],
            ),
            'position' => $position,
        ];
    }

    private function resolveType(UploadedFile $file): string
    {
        $mimeType = (string) $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return ProductMedia::TYPE_IMAGE;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return ProductMedia::TYPE_VIDEO;
        }

        throw ValidationException::withMessages([
            'uploads' => [__('admin.products.validation.media_type_invalid')],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalized
     */
    private function validateMediaLimits(array $normalized): void
    {
        $imageCount = 0;
        $videoCount = 0;
        $maxImages = (int) config('product.media.max_images', 9);
        $maxVideos = (int) config('product.media.max_videos', 1);
        $maxVideoSize = (int) config('product.media.max_video_size', 50 * 1024 * 1024);

        foreach ($normalized as $item) {
            if ($item['type'] === ProductMedia::TYPE_IMAGE) {
                $imageCount++;
            }

            if ($item['type'] === ProductMedia::TYPE_VIDEO) {
                $videoCount++;

                if (($item['file'] instanceof UploadedFile) && $item['file']->getSize() > $maxVideoSize) {
                    throw ValidationException::withMessages([
                        'uploads' => [__('admin.products.validation.video_too_large')],
                    ]);
                }
            }
        }

        if ($imageCount > $maxImages) {
            throw ValidationException::withMessages([
                'media' => [__('admin.products.validation.max_images')],
            ]);
        }

        if ($videoCount > $maxVideos) {
            throw ValidationException::withMessages([
                'media' => [__('admin.products.validation.max_videos')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, int>  $skuIdMap
     */
    private function resolveSkuId(Product $product, array $definition, array $skuIdMap): ?int
    {
        $skuKey = trim((string) ($definition['sku_key'] ?? ''));

        if ($skuKey !== '' && array_key_exists($skuKey, $skuIdMap)) {
            return $skuIdMap[$skuKey];
        }

        $productSkuId = isset($definition['product_sku_id']) ? (int) $definition['product_sku_id'] : null;

        if (! $productSkuId) {
            return null;
        }

        return $product->skus()->whereKey($productSkuId)->exists() ? $productSkuId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectMetadata(UploadedFile $file, string $type): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        if ($type !== ProductMedia::TYPE_IMAGE) {
            return $metadata;
        }

        $imageInfo = @getimagesize($file->getRealPath());

        if (! is_array($imageInfo)) {
            return $metadata;
        }

        return array_replace($metadata, [
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResizeSettings(mixed $resizeSettings): array
    {
        if (! is_array($resizeSettings)) {
            return [];
        }

        $widths = array_values(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, (array) ($resizeSettings['widths'] ?? [])),
            static fn (int $value): bool => $value > 0,
        ));

        return $widths !== [] ? ['widths' => $widths] : [];
    }

    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();

        return Str::uuid()->toString() . ($extension ? '.' . $extension : '');
    }
}

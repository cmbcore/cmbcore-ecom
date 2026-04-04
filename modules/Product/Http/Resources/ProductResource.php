<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Product\Models\Product;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        $skus = $product->relationLoaded('skus') ? $product->skus : collect();
        $media = $product->relationLoaded('media') ? $product->media : collect();
        $primaryMedia = $media->firstWhere('type', 'image') ?? $media->first();
        $flashSaleService = app(FlashSaleService::class);
        $effectivePrices = $skus->map(fn ($sku): float => $flashSaleService->effectivePrice($sku));
        $effectiveComparePrices = $skus
            ->map(fn ($sku): ?float => $flashSaleService->effectiveComparePrice($sku))
            ->filter(static fn (mixed $value): bool => $value !== null)
            ->values();
        $flashSale = $flashSaleService->activeSaleForProduct($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'description_html' => $product->description,
            'short_description' => $product->short_description,
            'short_description_html' => $product->short_description,
            'status' => $product->status,
            'type' => $product->type,
            'category_id' => $product->category_id,
            'brand' => $product->brand,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'view_count' => $product->view_count,
            'rating_value' => $product->rating_value !== null ? (float) $product->rating_value : null,
            'review_count' => (int) ($product->review_count ?? 0),
            'sold_count' => (int) ($product->sold_count ?? 0),
            'is_featured' => (bool) $product->is_featured,
            'sku_count' => $product->sku_count ?? $skus->count(),
            'media_count' => $product->media_count ?? $media->count(),
            'total_stock' => (int) ($product->total_stock ?? $skus->sum('stock_quantity')),
            'min_price' => $product->min_price !== null
                ? ($effectivePrices->count() > 0 ? (float) $effectivePrices->min() : (float) $product->min_price)
                : ($effectivePrices->count() > 0 ? (float) $effectivePrices->min() : null),
            'max_price' => $product->max_price !== null
                ? ($effectivePrices->count() > 0 ? (float) $effectivePrices->max() : (float) $product->max_price)
                : ($effectivePrices->count() > 0 ? (float) $effectivePrices->max() : null),
            'min_compare_price' => $effectiveComparePrices->count() > 0
                ? (float) $effectiveComparePrices->min()
                : ($product->min_compare_price !== null
                    ? (float) $product->min_compare_price
                    : ($skus->count() > 0 ? $this->numericOrNull($skus->pluck('compare_price')->filter()->min()) : null)),
            'max_compare_price' => $effectiveComparePrices->count() > 0
                ? (float) $effectiveComparePrices->max()
                : ($product->max_compare_price !== null
                    ? (float) $product->max_compare_price
                    : ($skus->count() > 0 ? $this->numericOrNull($skus->pluck('compare_price')->filter()->max()) : null)),
            'primary_media_type' => $primaryMedia?->type,
            'primary_media_url' => $primaryMedia
                ? MediaUrl::resolve($primaryMedia->path, (string) ($primaryMedia->disk ?: 'public'))
                : null,
            'flash_sale' => $flashSale,
            'category' => $product->relationLoaded('category') && $product->category
                ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ]
                : null,
            'skus' => $product->relationLoaded('skus')
                ? ProductSkuResource::collection($product->skus)->resolve()
                : [],
            'media' => $product->relationLoaded('media')
                ? ProductMediaResource::collection($product->media)->resolve()
                : [],
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
            'deleted_at' => $product->deleted_at?->toISOString(),
        ];
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}

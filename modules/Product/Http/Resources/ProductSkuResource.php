<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Product\Models\ProductSku;

class ProductSkuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductSku $sku */
        $sku = $this->resource;
        $flashSaleService = app(FlashSaleService::class);

        return [
            'id' => $sku->id,
            'product_id' => $sku->product_id,
            'sku_code' => $sku->sku_code,
            'name' => $sku->name,
            'price' => $sku->price !== null ? $flashSaleService->effectivePrice($sku) : null,
            'compare_price' => $flashSaleService->effectiveComparePrice($sku),
            'base_price' => $sku->price !== null ? (float) $sku->price : null,
            'cost' => $sku->cost !== null ? (float) $sku->cost : null,
            'weight' => $sku->weight !== null ? (float) $sku->weight : null,
            'stock_quantity' => $sku->stock_quantity,
            'low_stock_threshold' => $sku->low_stock_threshold,
            'barcode' => $sku->barcode,
            'status' => $sku->status,
            'sort_order' => $sku->sort_order,
            'flash_sale' => $flashSaleService->saleContextForSku($sku),
            'attributes' => $sku->relationLoaded('attributes')
                ? $sku->attributes->map(static fn ($attribute): array => [
                    'id' => $attribute->id,
                    'attribute_name' => $attribute->attribute_name,
                    'attribute_value' => $attribute->attribute_value,
                ])->values()->all()
                : [],
            'media_ids' => $sku->relationLoaded('media')
                ? $sku->media->pluck('id')->all()
                : [],
            'created_at' => $sku->created_at?->toISOString(),
            'updated_at' => $sku->updated_at?->toISOString(),
        ];
    }
}

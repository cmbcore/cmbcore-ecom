<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\ProductMedia;

class ProductMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductMedia $media */
        $media = $this->resource;

        return [
            'id' => $media->id,
            'product_id' => $media->product_id,
            'product_sku_id' => $media->product_sku_id,
            'type' => $media->type,
            'path' => $media->path,
            'url' => MediaUrl::resolve($media->path, $media->disk),
            'disk' => $media->disk,
            'filename' => $media->filename,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'position' => $media->position,
            'alt_text' => $media->alt_text,
            'metadata' => $media->metadata ?? [],
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}

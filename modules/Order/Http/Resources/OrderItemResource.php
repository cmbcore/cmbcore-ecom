<?php

declare(strict_types=1);

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Order\Models\OrderItem;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OrderItem $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_sku_id' => $item->product_sku_id,
            'product_name' => $item->product_name,
            'sku_name' => $item->sku_name,
            'attributes' => $item->attributes_snapshot ?? [],
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'line_total' => (float) $item->line_total,
            'product_snapshot' => $item->product_snapshot ?? [],
        ];
    }
}

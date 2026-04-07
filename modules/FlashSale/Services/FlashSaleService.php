<?php

declare(strict_types=1);

namespace Modules\FlashSale\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\FlashSale\Models\FlashSale;
use Modules\FlashSale\Models\FlashSaleItem;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class FlashSaleService
{
    private ?bool $tablesAvailable = null;

    /**
     * @var array<int, FlashSaleItem|null>
     */
    private array $activeItemCache = [];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return FlashSale::query()
            ->with(['items.product', 'items.sku'])
            ->latest('id')
            ->get()
            ->map(fn (FlashSale $sale): array => [
                'id' => $sale->id,
                'title' => $sale->title,
                'starts_at' => $sale->starts_at?->toISOString(),
                'ends_at' => $sale->ends_at?->toISOString(),
                'is_active' => (bool) $sale->is_active,
                'items' => $sale->items->map(fn (FlashSaleItem $item): array => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_sku_id' => $item->product_sku_id,
                    'product_name' => $item->product?->name,
                    'sku_name' => $item->sku?->name,
                    'sale_price' => (float) $item->sale_price,
                    'quantity_limit' => $item->quantity_limit,
                    'sold_quantity' => $item->sold_quantity,
                    'remaining_quantity' => $item->quantity_limit !== null
                        ? max(0, (int) $item->quantity_limit - (int) $item->sold_quantity)
                        : null,
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload): FlashSale
    {
        $sale = DB::transaction(function () use ($payload): FlashSale {
            /** @var FlashSale $sale */
            $sale = FlashSale::query()->updateOrCreate(
                ['id' => isset($payload['id']) ? (int) $payload['id'] : null],
                [
                    'title' => trim((string) ($payload['title'] ?? '')),
                    'starts_at' => $payload['starts_at'],
                    'ends_at' => $payload['ends_at'],
                    'is_active' => (bool) ($payload['is_active'] ?? true),
                ],
            );

            $existingItems = $sale->items()->get()->keyBy('id');
            $keptIds = [];

            foreach (array_values(array_filter((array) ($payload['items'] ?? []), 'is_array')) as $itemPayload) {
                $skuId = isset($itemPayload['product_sku_id']) ? (int) $itemPayload['product_sku_id'] : null;
                $sku = $skuId ? ProductSku::query()->find($skuId) : null;
                $itemData = [
                    'product_id' => $sku?->product_id ?? (isset($itemPayload['product_id']) ? (int) $itemPayload['product_id'] : null),
                    'product_sku_id' => $skuId,
                    'sale_price' => (float) ($itemPayload['sale_price'] ?? 0),
                    'quantity_limit' => isset($itemPayload['quantity_limit']) && $itemPayload['quantity_limit'] !== null
                        ? max(1, (int) $itemPayload['quantity_limit'])
                        : null,
                    'sold_quantity' => max(0, (int) ($itemPayload['sold_quantity'] ?? 0)),
                ];

                $itemId = isset($itemPayload['id']) ? (int) $itemPayload['id'] : null;

                if ($itemId !== null && $existingItems->has($itemId)) {
                    /** @var FlashSaleItem $flashSaleItem */
                    $flashSaleItem = $existingItems->get($itemId);
                    $flashSaleItem->forceFill($itemData)->save();
                    $keptIds[] = $flashSaleItem->id;

                    continue;
                }

                $keptIds[] = $sale->items()->create($itemData)->id;
            }

            if ($keptIds !== []) {
                $sale->items()->whereNotIn('id', $keptIds)->delete();
            } else {
                $sale->items()->delete();
            }

            return $sale;
        });

        $this->activeItemCache = [];

        return $sale->refresh()->load(['items.product', 'items.sku']);
    }

    public function delete(int $id): void
    {
        FlashSale::query()->findOrFail($id)->delete();
        $this->activeItemCache = [];
    }

    public function effectivePrice(ProductSku $sku): float
    {
        if (! $this->isReady()) {
            return (float) $sku->price;
        }

        $item = $this->activeItemForSku($sku);

        return $item instanceof FlashSaleItem ? (float) $item->sale_price : (float) $sku->price;
    }

    public function effectiveComparePrice(ProductSku $sku): ?float
    {
        $effectivePrice = $this->effectivePrice($sku);
        $basePrice = $sku->price !== null ? (float) $sku->price : null;
        $comparePrice = $sku->compare_price !== null ? (float) $sku->compare_price : null;

        if ($basePrice !== null && $effectivePrice < $basePrice) {
            return $comparePrice !== null
                ? max($basePrice, $comparePrice)
                : $basePrice;
        }

        return $comparePrice;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function saleContextForSku(ProductSku $sku): ?array
    {
        $item = $this->activeItemForSku($sku);

        if (! $item instanceof FlashSaleItem || ! $item->flashSale) {
            return null;
        }

        if ($item->quantity_limit !== null && $item->sold_quantity >= $item->quantity_limit) {
            return null;
        }

        return [
            'item_id' => $item->id,
            'flash_sale_id' => $item->flash_sale_id,
            'title' => $item->flashSale->title,
            'sale_price' => (float) $item->sale_price,
            'quantity_limit' => $item->quantity_limit,
            'sold_quantity' => (int) $item->sold_quantity,
            'remaining_quantity' => $item->quantity_limit !== null
                ? max(0, (int) $item->quantity_limit - (int) $item->sold_quantity)
                : null,
            'starts_at' => $item->flashSale->starts_at?->toISOString(),
            'ends_at' => $item->flashSale->ends_at?->toISOString(),
        ];
    }

    public function assertItemQuantity(ProductSku $sku, int $quantity, bool $lockForUpdate = false): void
    {
        $query = FlashSaleItem::query()
            ->with('flashSale')
            ->where('product_sku_id', $sku->id)
            ->whereHas('flashSale', fn ($query) => $query
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $item = $query->first();

        if (! $item instanceof FlashSaleItem || $item->quantity_limit === null) {
            return;
        }

        $remaining = max(0, (int) $item->quantity_limit - (int) $item->sold_quantity);
        abort_if($quantity > $remaining, 422, 'Số lượng flash sale còn lại không đủ.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function assertAvailability(array $items, bool $lockForUpdate = false): void
    {
        foreach ($items as $item) {
            if (! isset($item['product_sku_id']) || ! is_numeric($item['product_sku_id'])) {
                continue;
            }

            /** @var ProductSku|null $sku */
            $sku = ProductSku::query()->find((int) $item['product_sku_id']);

            if (! $sku instanceof ProductSku) {
                continue;
            }

            $this->assertItemQuantity($sku, max(1, (int) ($item['quantity'] ?? 1)), $lockForUpdate);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeSaleForProduct(Product $product): ?array
    {
        if (! $this->isReady()) {
            return null;
        }

        $skuIds = $product->relationLoaded('skus')
            ? $product->skus->pluck('id')->all()
            : $product->skus()->pluck('id')->all();

        /** @var FlashSaleItem|null $item */
        $item = FlashSaleItem::query()
            ->with('flashSale')
            ->whereIn('product_sku_id', $skuIds)
            ->whereHas('flashSale', fn ($query) => $query
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()))
            ->orderBy('sale_price')
            ->first();

        if (! $item instanceof FlashSaleItem || ! $item->flashSale) {
            return null;
        }

        return [
            'item_id' => $item->id,
            'title' => $item->flashSale->title,
            'sale_price' => (float) $item->sale_price,
            'quantity_limit' => $item->quantity_limit,
            'sold_quantity' => (int) $item->sold_quantity,
            'remaining_quantity' => $item->quantity_limit !== null
                ? max(0, (int) $item->quantity_limit - (int) $item->sold_quantity)
                : null,
            'ends_at' => $item->flashSale->ends_at?->toISOString(),
        ];
    }

    public function recordConfirmedOrder(Order $order): void
    {
        if (! $this->isReady()) {
            return;
        }

        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $snapshot = $this->flashSaleSnapshot($item);
                $flashSaleItemId = isset($snapshot['item_id']) ? (int) $snapshot['item_id'] : null;

                if ($flashSaleItemId === null || ! empty($snapshot['confirmed_at'])) {
                    continue;
                }

                /** @var FlashSaleItem|null $flashSaleItem */
                $flashSaleItem = FlashSaleItem::query()->find($flashSaleItemId);

                if (! $flashSaleItem instanceof FlashSaleItem) {
                    continue;
                }

                $flashSaleItem->increment('sold_quantity', (int) $item->quantity);
                $flashSaleItem->refresh();
                $this->storeFlashSaleSnapshot($item, array_replace($snapshot, [
                    'confirmed_at' => now()->toISOString(),
                    'sold_quantity' => (int) $flashSaleItem->sold_quantity,
                    'remaining_quantity' => $flashSaleItem->quantity_limit !== null
                        ? max(0, (int) $flashSaleItem->quantity_limit - (int) $flashSaleItem->sold_quantity)
                        : null,
                ]));
                unset($this->activeItemCache[$flashSaleItem->product_sku_id ?? 0]);
            }
        });
    }

    public function restoreCancelledOrder(Order $order): void
    {
        if (! $this->isReady()) {
            return;
        }

        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $snapshot = $this->flashSaleSnapshot($item);
                $flashSaleItemId = isset($snapshot['item_id']) ? (int) $snapshot['item_id'] : null;

                if ($flashSaleItemId === null || empty($snapshot['confirmed_at']) || ! empty($snapshot['reverted_at'])) {
                    continue;
                }

                /** @var FlashSaleItem|null $flashSaleItem */
                $flashSaleItem = FlashSaleItem::query()->find($flashSaleItemId);

                if (! $flashSaleItem instanceof FlashSaleItem) {
                    continue;
                }

                $nextSoldQuantity = max(0, (int) $flashSaleItem->sold_quantity - (int) $item->quantity);
                $flashSaleItem->forceFill(['sold_quantity' => $nextSoldQuantity])->save();
                $this->storeFlashSaleSnapshot($item, array_replace($snapshot, [
                    'reverted_at' => now()->toISOString(),
                    'sold_quantity' => $nextSoldQuantity,
                    'remaining_quantity' => $flashSaleItem->quantity_limit !== null
                        ? max(0, (int) $flashSaleItem->quantity_limit - $nextSoldQuantity)
                        : null,
                ]));
                unset($this->activeItemCache[$flashSaleItem->product_sku_id ?? 0]);
            }
        });
    }

    private function activeItemForSku(ProductSku $sku): ?FlashSaleItem
    {
        if (! $this->isReady()) {
            return null;
        }

        if (array_key_exists($sku->id, $this->activeItemCache)) {
            return $this->activeItemCache[$sku->id];
        }

        /** @var FlashSaleItem|null $item */
        $item = FlashSaleItem::query()
            ->with('flashSale')
            ->where('product_sku_id', $sku->id)
            ->whereHas('flashSale', fn ($query) => $query
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()))
            ->orderBy('sale_price')
            ->first();

        if (! $item instanceof FlashSaleItem) {
            return $this->activeItemCache[$sku->id] = null;
        }

        if ($item->quantity_limit !== null && $item->sold_quantity >= $item->quantity_limit) {
            return $this->activeItemCache[$sku->id] = null;
        }

        return $this->activeItemCache[$sku->id] = $item;
    }

    private function isReady(): bool
    {
        if ($this->tablesAvailable !== null) {
            return $this->tablesAvailable;
        }

        try {
            // Cache schema check across all requests to avoid repeated DB metadata queries
            return $this->tablesAvailable = (bool) cache()->remember(
                'flash_sale_tables_ready',
                now()->addMinutes(10),
                static fn (): bool => Schema::hasTable('flash_sales') && Schema::hasTable('flash_sale_items'),
            );
        } catch (\Throwable) {
            return $this->tablesAvailable = false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function flashSaleSnapshot(OrderItem $item): array
    {
        $snapshot = $item->product_snapshot;

        return is_array($snapshot['flash_sale'] ?? null) ? $snapshot['flash_sale'] : [];
    }

    /**
     * @param  array<string, mixed>  $flashSaleSnapshot
     */
    private function storeFlashSaleSnapshot(OrderItem $item, array $flashSaleSnapshot): void
    {
        $productSnapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];
        $productSnapshot['flash_sale'] = $flashSaleSnapshot;

        $item->forceFill([
            'product_snapshot' => $productSnapshot,
        ])->save();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockMovement;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class InventoryService
{
    /**
     * Check stock availability for all items in a single batch query (fixes N+1).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  bool  $lockForUpdate  Pass true only when already inside a DB::transaction
     *                               (i.e. from placeOrder). Passing true outside a transaction
     *                               will cause a DB error on most engines.
     */
    public function assertAvailable(array $items, bool $lockForUpdate = false): void
    {
        $skuIds = array_map(static fn ($item): int => (int) $item['product_sku_id'], $items);
        $quantityMap = [];

        foreach ($items as $item) {
            $quantityMap[(int) $item['product_sku_id']] = (int) $item['quantity'];
        }

        $query = ProductSku::query()->whereIn('id', $skuIds);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $skus = $query->get()->keyBy('id');

        foreach ($quantityMap as $skuId => $quantity) {
            $sku = $skus->get($skuId);

            if (! $sku instanceof ProductSku) {
                abort(422, 'Một hoặc nhiều SKU không tồn tại hoặc không hoạt động.');
            }

            abort_if($quantity > (int) $sku->stock_quantity, 422, 'Một hoặc nhiều sản phẩm đã vượt quá số lượng tồn kho.');
        }
    }

    public function deductForOrder(Order $order): void
    {
        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            // Filter items that need deduction and haven't been processed
            $pendingItems = $order->items->filter(static fn ($item): bool =>
                $item->product_sku_id !== null &&
                ! StockMovement::query()
                    ->where('order_id', $order->id)
                    ->where('sku_id', $item->product_sku_id)
                    ->where('type', StockMovement::TYPE_SALE)
                    ->exists()
            );

            if ($pendingItems->isEmpty()) {
                return;
            }

            // Batch load + pessimistic lock to prevent race conditions when
            // multiple orders for the same SKU are confirmed concurrently.
            $skuIds = $pendingItems->pluck('product_sku_id')->unique()->values()->all();
            $skus = ProductSku::query()
                ->whereIn('id', $skuIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($pendingItems as $item) {
                /** @var ProductSku|null $sku */
                $sku = $skus->get($item->product_sku_id);

                if (! $sku instanceof ProductSku) {
                    continue;
                }

                $sku->decrement('stock_quantity', (int) $item->quantity);

                if ($item->product_id) {
                    Product::query()->whereKey($item->product_id)->increment('sold_count', (int) $item->quantity);
                }

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'sku_id' => $sku->id,
                    'order_id' => $order->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -1 * (int) $item->quantity,
                    'reference' => $order->order_number,
                    'note' => 'Trừ kho khi xác nhận đơn hàng.',
                ]);
            }
        });
    }

    public function restoreForOrder(Order $order): void
    {
        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $saleExists = StockMovement::query()
                    ->where('order_id', $order->id)
                    ->where('sku_id', $item->product_sku_id)
                    ->where('type', StockMovement::TYPE_SALE)
                    ->exists();

                $alreadyRestored = StockMovement::query()
                    ->where('order_id', $order->id)
                    ->where('sku_id', $item->product_sku_id)
                    ->where('type', StockMovement::TYPE_RETURN)
                    ->exists();

                if (! $saleExists || $alreadyRestored || ! $item->product_sku_id) {
                    continue;
                }

                /** @var ProductSku $sku */
                $sku = ProductSku::query()->findOrFail($item->product_sku_id);
                $sku->increment('stock_quantity', (int) $item->quantity);

                if ($item->product_id) {
                    Product::query()->whereKey($item->product_id)->decrement('sold_count', (int) $item->quantity);
                }

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'sku_id' => $sku->id,
                    'order_id' => $order->id,
                    'type' => StockMovement::TYPE_RETURN,
                    'quantity' => (int) $item->quantity,
                    'reference' => $order->order_number,
                    'note' => 'Hoàn kho khi hủy đơn hang.',
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $skuOptions = ProductSku::query()
            ->with('product')
            ->where('status', ProductSku::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit((int) config('inventory.sku_option_limit', 500))
            ->get()
            ->map(fn (ProductSku $sku): array => [
                'value' => $sku->id,
                'label' => trim(implode(' - ', array_filter([
                    $sku->product?->name,
                    $sku->name,
                    $sku->sku_code ? '(' . $sku->sku_code . ')' : null,
                ]))),
                'product_name' => $sku->product?->name,
                'sku_name' => $sku->name,
                'sku_code' => $sku->sku_code,
                'stock_quantity' => (int) $sku->stock_quantity,
                'low_stock_threshold' => (int) $sku->low_stock_threshold,
            ])
            ->all();

        $lowStockSkus = ProductSku::query()
            ->with('product')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('status', ProductSku::STATUS_ACTIVE)
            ->orderBy('stock_quantity')
            ->limit(50)
            ->get()
            ->map(fn (ProductSku $sku): array => [
                'id' => $sku->id,
                'product_name' => $sku->product?->name,
                'sku_name' => $sku->name,
                'sku_code' => $sku->sku_code,
                'stock_quantity' => (int) $sku->stock_quantity,
                'low_stock_threshold' => (int) $sku->low_stock_threshold,
            ])
            ->all();

        $movements = StockMovement::query()
            ->with(['sku', 'product', 'order'])
            ->latest('id')
            ->limit((int) config('inventory.movement_limit', 50))
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'product_name' => $movement->product?->name,
                'sku_name' => $movement->sku?->name,
                'type' => $movement->type,
                'quantity' => (int) $movement->quantity,
                'reference' => $movement->reference,
                'note' => $movement->note,
                'created_at' => $movement->created_at?->toISOString(),
            ])
            ->all();

        return [
            'low_stock' => $lowStockSkus,
            'movements' => $movements,
            'sku_options' => $skuOptions,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function bulkUpdate(array $items): void
    {
        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                if (! isset($item['sku_id']) || ! is_numeric($item['sku_id'])) {
                    continue;
                }

                /** @var ProductSku $sku */
                $sku = ProductSku::query()->findOrFail((int) $item['sku_id']);
                $previousStock = (int) $sku->stock_quantity;
                $nextStock = max(0, (int) ($item['stock_quantity'] ?? $previousStock));

                $sku->forceFill([
                    'stock_quantity' => $nextStock,
                    'low_stock_threshold' => max(0, (int) ($item['low_stock_threshold'] ?? $sku->low_stock_threshold)),
                ])->save();

                if ($previousStock !== $nextStock) {
                    StockMovement::query()->create([
                        'product_id' => $sku->product_id,
                        'sku_id' => $sku->id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'quantity' => $nextStock - $previousStock,
                        'reference' => $sku->sku_code,
                        'note' => 'Cập nhật ton kho tu admin.',
                    ]);
                }
            }
        });
    }
}

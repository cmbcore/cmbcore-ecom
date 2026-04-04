<?php

declare(strict_types=1);

namespace Modules\FlashSale\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FlashSale\Models\FlashSaleItem;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Product\Models\ProductSku;

class FlashSaleController extends Controller
{
    public function __construct(
        private readonly FlashSaleService $flashSaleService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->flashSaleService->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:flash_sales,id'],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:flash_sale_items,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_limit' => ['nullable', 'integer', 'min:1'],
            'items.*.sold_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->flashSaleService->save($payload),
            'message' => 'Da luu flash sale.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->flashSaleService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa flash sale.',
        ]);
    }

    /**
     * Return all active SKUs with a flag indicating if they are
     * already used by a currently active flash sale (optionally excluding a given sale).
     */
    public function skuOptions(Request $request): JsonResponse
    {
        $excludeSaleId = $request->query('exclude_sale_id') ? (int) $request->query('exclude_sale_id') : null;

        // SKU IDs already in an active flash sale
        $busySkuIds = FlashSaleItem::query()
            ->whereHas('flashSale', fn ($q) => $q
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->when($excludeSaleId, fn ($q2) => $q2->where('id', '!=', $excludeSaleId)))
            ->pluck('product_sku_id')
            ->unique()
            ->values()
            ->all();

        $skus = ProductSku::query()
            ->with('product:id,name,slug')
            ->where('status', ProductSku::STATUS_ACTIVE)
            ->whereHas('product', fn ($q) => $q->where('status', 'active'))
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductSku $sku): array => [
                'sku_id'       => $sku->id,
                'product_id'   => $sku->product_id,
                'product_name' => $sku->product?->name,
                'sku_name'     => $sku->name,
                'sku_code'     => $sku->sku_code,
                'price'        => (float) $sku->price,
                'stock'        => (int) $sku->stock_quantity,
                'is_busy'      => in_array($sku->id, $busySkuIds, true),
            ])
            ->all();

        return response()->json([
            'success' => true,
            'data'    => $skus,
        ]);
    }
}

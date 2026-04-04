<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryService;

class InventoryAdminController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->inventoryService->dashboard(),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*.sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'items.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'items.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->inventoryService->bulkUpdate($payload['items']);

        return response()->json([
            'success' => true,
            'message' => 'Da cập nhật ton kho.',
        ]);
    }
}

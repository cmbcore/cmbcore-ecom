<?php

declare(strict_types=1);

namespace Modules\Shipping\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shipping\Services\ShippingService;

class ShippingAdminController extends Controller
{
    public function __construct(
        private readonly ShippingService $shippingService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->shippingService->adminPayload(),
        ]);
    }

    public function saveZone(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'provinces' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->shippingService->saveZone($payload),
            'message' => 'Da luu khu vực giao hang.',
        ]);
    }

    public function deleteZone(int $id): JsonResponse
    {
        $this->shippingService->deleteZone($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa khu vực giao hang.',
        ]);
    }

    public function saveMethod(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'shipping_zone_id' => ['nullable', 'integer', 'exists:shipping_zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:flat_rate,free,calculated'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_order_value' => ['nullable', 'numeric', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->shippingService->saveMethod($payload),
            'message' => 'Da luu phuong thuc van chuyen.',
        ]);
    }

    public function deleteMethod(int $id): JsonResponse
    {
        $this->shippingService->deleteMethod($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa phuong thuc van chuyen.',
        ]);
    }
}

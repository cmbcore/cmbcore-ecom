<?php

declare(strict_types=1);

namespace Modules\Coupon\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Services\CouponService;

class CouponAdminController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $coupons = $this->couponService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:coupons,id'],
            'code' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->couponService->save($payload),
            'message' => 'Da luu coupon.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->couponService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa coupon.',
        ]);
    }
}

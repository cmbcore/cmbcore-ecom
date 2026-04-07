<?php

declare(strict_types=1);

namespace Modules\Coupon\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Services\CartService;
use Modules\Coupon\Services\CouponService;

class CouponPreviewController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CartService $cartService,
    ) {
    }

    /**
     * POST /api/storefront/coupon/preview
     *
     * Kiểm tra và tính giá trị giảm giá của coupon trước khi đặt hàng.
     * Không yêu cầu xác thực — khách hàng cũng có thể dùng.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:100'],
            'subtotal'    => ['nullable', 'numeric', 'min:0'],
            'mode'        => ['nullable', 'in:cart,buy_now'],
        ]);

        $user = $request->user();

        // Determine subtotal: caller can pass it directly (from JS) or we compute from cart
        if (isset($data['subtotal'])) {
            $subtotal = (float) $data['subtotal'];
        } else {
            $mode = ($data['mode'] ?? 'cart') === 'buy_now' ? 'buy_now' : 'cart';
            $cartData = $mode === 'cart'
                ? $this->cartService->activePayload($user)
                : [];
            $subtotal = (float) ($cartData['subtotal'] ?? 0);
        }

        $quote = $this->couponService->quote(
            code: $data['coupon_code'],
            subtotal: $subtotal,
            user: $user,
            guestEmail: null,
            strict: false,  // Never abort — always return error in JSON
        );

        if (! $quote['applied']) {
            return response()->json([
                'success' => false,
                'message' => $quote['error'] ?? 'Coupon không hợp lệ.',
            ], 422);
        }

        return response()->json([
            'success'        => true,
            'code'           => $quote['code'],
            'discount_total' => $quote['discount_total'],
            'message'        => "Áp dụng thành công! Giảm " . number_format($quote['discount_total'], 0, ',', '.') . ' ₫',
        ]);
    }
}

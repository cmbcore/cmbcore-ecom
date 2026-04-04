<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Order\Http\Requests\CheckoutPreviewRequest;
use Modules\Order\Http\Requests\PlaceOrderRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Services\OrderService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function preview(CheckoutPreviewRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->orderService->previewCheckout($request->validated(), $request->user()),
        ]);
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'data' => (new OrderResource($order))->resolve(),
            'message' => __('frontend.checkout.messages.order_created'),
        ], 201);
    }

    public function buyNowPreview(CheckoutPreviewRequest $request): JsonResponse
    {
        $payload = array_merge($request->validated(), ['mode' => 'buy_now']);

        return response()->json([
            'success' => true,
            'data' => $this->orderService->previewCheckout($payload, $request->user()),
        ]);
    }
}

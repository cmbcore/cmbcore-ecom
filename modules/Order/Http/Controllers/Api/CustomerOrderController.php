<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Services\OrderService;

class CustomerOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(): JsonResponse
    {
        $orders = $this->orderService->listForCustomer(request()->user());

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->getCollection())->resolve(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->findForCustomer(request()->user(), $orderNumber);

        return response()->json([
            'success' => true,
            'data' => (new OrderResource($order))->resolve(),
        ]);
    }
}

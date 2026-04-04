<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Http\Requests\UpdateOrderStatusRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Services\OrderService;

class OrderAdminController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->paginateForAdmin($request->all());

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

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->findForAdmin($id);

        return response()->json([
            'success' => true,
            'data' => (new OrderResource($order))->resolve(),
        ]);
    }

    public function update(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->findForAdmin($id);
        $order = $this->orderService->updateStatuses($order, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'data' => (new OrderResource($order))->resolve(),
        ]);
    }
}

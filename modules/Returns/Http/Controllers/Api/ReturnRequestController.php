<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Services\ReturnRequestService;

class ReturnRequestController extends Controller
{
    public function __construct(
        private readonly ReturnRequestService $returnRequestService,
    ) {
    }

    public function index(): JsonResponse
    {
        $requests = $this->returnRequestService->paginate();

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request, string $orderNumber): JsonResponse
    {
        /** @var Order $order */
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
        $payload = $request->validate([
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'requested_quantity' => ['nullable', 'integer', 'min:1'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->returnRequestService->create($request->user(), $order, $payload),
            'message' => 'Da gui yêu cầu đổi trả.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var ReturnRequest $returnRequest */
        $returnRequest = ReturnRequest::query()->with(['order', 'item', 'user'])->findOrFail($id);
        $payload = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,refunded'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'resolution_note' => ['nullable', 'string'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->returnRequestService->update($returnRequest, $payload),
            'message' => 'Da cập nhật yêu cầu đổi trả.',
        ]);
    }
}

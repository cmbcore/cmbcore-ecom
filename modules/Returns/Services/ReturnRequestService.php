<?php

declare(strict_types=1);

namespace Modules\Returns\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Order\Models\Order;
use Modules\Payment\Services\PaymentService;
use Modules\Returns\Models\ReturnRequest;

class ReturnRequestService
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $user, Order $order, array $payload): ReturnRequest
    {
        abort_unless($order->user_id === $user->id, 403);
        abort_unless($order->fulfillment_status === Order::FULFILLMENT_STATUS_DELIVERED, 422, 'Chi don da giao moi duoc yêu cầu đổi trả.');

        $orderItemId = isset($payload['order_item_id']) ? (int) $payload['order_item_id'] : null;
        $requestedQuantity = max(1, (int) ($payload['requested_quantity'] ?? 1));
        $order->loadMissing('items');

        if ($orderItemId !== null) {
            $orderItem = $order->items->firstWhere('id', $orderItemId);

            abort_if($orderItem === null, 422, 'Sản phẩm đổi trả khong thuộc đơn hang nay.');
            abort_if($requestedQuantity > (int) $orderItem->quantity, 422, 'So luong đổi trả vuot qua so luong đã mua.');
        }

        abort_if(
            ReturnRequest::query()
                ->where('order_id', $order->id)
                ->when($orderItemId !== null, fn ($query) => $query->where('order_item_id', $orderItemId))
                ->whereIn('status', ['pending', 'approved'])
                ->exists(),
            422,
            'Yêu cầu đổi trả cho đơn hang hoac sản phẩm nay dang duoc xử lý.',
        );

        return ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $orderItemId,
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_quantity' => $requestedQuantity,
            'refund_amount' => $payload['refund_amount'] ?? null,
            'reason' => trim((string) $payload['reason']),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, ReturnRequest>
     */
    public function paginate(): LengthAwarePaginator
    {
        return ReturnRequest::query()
            ->with(['order', 'item', 'user'])
            ->latest('id')
            ->paginate(20);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forCustomer(User $user): array
    {
        return ReturnRequest::query()
            ->with(['order', 'item'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get()
            ->map(fn (ReturnRequest $request): array => [
                'id' => $request->id,
                'status' => $request->status,
                'requested_quantity' => (int) $request->requested_quantity,
                'refund_amount' => $request->refund_amount !== null ? (float) $request->refund_amount : null,
                'reason' => $request->reason,
                'resolution_note' => $request->resolution_note,
                'created_at' => $request->created_at?->toISOString(),
                'order' => $request->order ? [
                    'order_number' => $request->order->order_number,
                ] : null,
                'item' => $request->item ? [
                    'product_name' => $request->item->product_name,
                    'sku_name' => $request->item->sku_name,
                ] : null,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(ReturnRequest $request, array $payload): ReturnRequest
    {
        $request->forceFill([
            'status' => $payload['status'],
            'resolution_note' => $payload['resolution_note'] ?? null,
            'refund_amount' => $payload['refund_amount'] ?? $request->refund_amount,
        ])->save();

        if ($request->status === 'refunded' && $request->refund_amount !== null) {
            $this->paymentService->refund($request->order, (float) $request->refund_amount, $request->resolution_note);
        }

        return $request->refresh()->load(['order', 'item', 'user']);
    }
}

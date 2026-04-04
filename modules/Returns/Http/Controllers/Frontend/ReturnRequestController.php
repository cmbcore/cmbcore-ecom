<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Order\Models\Order;
use Modules\Returns\Services\ReturnRequestService;

class ReturnRequestController extends Controller
{
    public function __construct(
        private readonly ReturnRequestService $returnRequestService,
    ) {
    }

    public function index(): View
    {
        $user = request()->user();

        return theme_manager()->view('account.returns', [
            'page' => [
                'title' => theme_text('account.returns_title'),
                'meta_title' => theme_text('account.returns_title'),
            ],
            'return_requests' => $this->returnRequestService->forCustomer($user),
            'eligible_orders' => Order::query()
                ->where('user_id', $user->id)
                ->where('fulfillment_status', Order::FULFILLMENT_STATUS_DELIVERED)
                ->with('items')
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(string $orderNumber): RedirectResponse
    {
        $payload = request()->validate([
            'order_item_id' => ['nullable', 'integer'],
            'requested_quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        /** @var Order $order */
        $order = Order::query()
            ->where('user_id', request()->user()->id)
            ->where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        $this->returnRequestService->create(request()->user(), $order, $payload);

        return redirect()->route('storefront.account.returns')
            ->with('status', __('frontend.account.messages.return_requested'));
    }
}

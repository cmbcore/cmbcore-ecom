<?php

declare(strict_types=1);

namespace Modules\Order\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Order\Http\Requests\PlaceOrderRequest;
use Modules\Order\Services\OrderService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $user = request()->user();
        $mode = request()->query('mode') === 'buy_now' && session()->has('checkout.buy_now')
            ? 'buy_now'
            : 'cart';

        if ($mode === 'buy_now' && ! session()->has('checkout.buy_now')) {
            return redirect()->route('storefront.cart.index')
                ->with('status', __('frontend.checkout.messages.empty_cart'));
        }

        $payload = $mode === 'buy_now'
            ? array_merge((array) session('checkout.buy_now'), ['mode' => 'buy_now'])
            : [];

        try {
            $preview = $this->orderService->previewCheckout($payload, $user);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return redirect()->route('storefront.cart.index')
                    ->with('status', $exception->getMessage());
            }

            throw $exception;
        }

        return theme_manager()->view('checkout.index', [
            'page' => [
                'title' => theme_text('checkout.title'),
                'meta_title' => theme_text('checkout.title'),
            ],
            'checkout' => $preview,
            'mode' => $mode,
            'customer' => $user,
            'addresses' => $user?->addresses()->get() ?? collect(),
        ]);
    }

    public function buyNow(): RedirectResponse
    {
        $data = request()->validate([
            'product_sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        session()->put('checkout.buy_now', $data);

        return redirect()->route('storefront.checkout.index', ['mode' => 'buy_now']);
    }

    public function placeOrder(PlaceOrderRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        if (($payload['mode'] ?? 'cart') === 'buy_now' && session()->has('checkout.buy_now')) {
            $payload = array_merge($payload, (array) session('checkout.buy_now'));
            $payload['mode'] = 'buy_now';
        }

        $order = $this->orderService->placeOrder($payload, $request->user());
        session()->forget('checkout.buy_now');

        return redirect()->route('storefront.orders.success', ['orderNumber' => $order->order_number])
            ->with('status', __('frontend.checkout.messages.order_created'));
    }

    public function success(string $orderNumber): View
    {
        return theme_manager()->view('orders.success', [
            'page' => [
                'title' => theme_text('checkout.success_title'),
                'meta_title' => theme_text('checkout.success_title'),
            ],
            'order_number' => $orderNumber,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Cart\Http\Requests\AddCartItemRequest;
use Modules\Cart\Http\Requests\UpdateCartItemRequest;
use Modules\Cart\Services\CartService;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function index(): View
    {
        $cart = $this->cartService->getOrCreateActiveCart(request()->user());

        return theme_manager()->view('cart.index', [
            'page' => [
                'title' => theme_text('cart.title'),
                'meta_title' => theme_text('cart.title'),
            ],
            'cart' => $this->cartService->payload($cart),
        ]);
    }

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $this->cartService->addItem($request->validated(), request()->user());

        return back()->with('status', __('frontend.cart.messages.item_added'));
    }

    public function update(UpdateCartItemRequest $request, int $id): RedirectResponse
    {
        $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
        $this->cartService->updateItem($item, (int) $request->validated()['quantity']);

        return back()->with('status', __('frontend.cart.messages.item_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
        $this->cartService->removeItem($item);

        return back()->with('status', __('frontend.cart.messages.item_removed'));
    }
}

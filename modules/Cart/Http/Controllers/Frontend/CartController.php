<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Modules\Cart\Http\Requests\AddCartItemRequest;
use Modules\Cart\Http\Requests\UpdateCartItemRequest;
use Modules\Cart\Services\CartService;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        try {
            $this->cartService->addItem($request->validated(), request()->user());
        } catch (HttpException|ModelNotFoundException $exception) {
            return back()->withErrors(['product_sku_id' => $this->errorMessage($exception)]);
        }

        return back()->with('status', __('frontend.cart.messages.item_added'));
    }

    public function update(UpdateCartItemRequest $request, int $id): RedirectResponse
    {
        try {
            $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
            $this->cartService->updateItem($item, (int) $request->validated()['quantity']);
        } catch (HttpException|ModelNotFoundException $exception) {
            return back()->withErrors(['quantity' => $this->errorMessage($exception)]);
        }

        return back()->with('status', __('frontend.cart.messages.item_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
            $this->cartService->removeItem($item);
        } catch (HttpException|ModelNotFoundException $exception) {
            return back()->withErrors(['cart' => $this->errorMessage($exception)]);
        }

        return back()->with('status', __('frontend.cart.messages.item_removed'));
    }

    private function errorMessage(HttpException|ModelNotFoundException $exception): string
    {
        if ($exception instanceof ModelNotFoundException) {
            return __('frontend.cart.messages.product_unavailable');
        }

        return $exception->getMessage() !== '' ? $exception->getMessage() : __('frontend.cart.messages.product_unavailable');
    }
}

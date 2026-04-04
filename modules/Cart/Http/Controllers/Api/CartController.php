<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Cart\Http\Requests\AddCartItemRequest;
use Modules\Cart\Http\Requests\UpdateCartItemRequest;
use Modules\Cart\Http\Resources\CartResource;
use Modules\Cart\Services\CartService;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function show(): JsonResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart(request()->user());

        return response()->json([
            'success' => true,
            'data' => (new CartResource($cart))->resolve(),
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem($request->validated(), request()->user());

        return response()->json([
            'success' => true,
            'data' => (new CartResource($cart))->resolve(),
            'message' => __('frontend.cart.messages.item_added'),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
        $cart = $this->cartService->updateItem($item, (int) $request->validated()['quantity']);

        return response()->json([
            'success' => true,
            'data' => (new CartResource($cart))->resolve(),
            'message' => __('frontend.cart.messages.item_updated'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = $this->cartService->resolveItemForCurrentContext($id, request()->user());
        $cart = $this->cartService->removeItem($item);

        return response()->json([
            'success' => true,
            'data' => (new CartResource($cart))->resolve(),
            'message' => __('frontend.cart.messages.item_removed'),
        ]);
    }

    public function merge(): JsonResponse
    {
        $cart = $this->cartService->mergeGuestCartIntoUser(request()->user());

        return response()->json([
            'success' => true,
            'data' => (new CartResource($cart))->resolve(),
        ]);
    }
}

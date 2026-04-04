<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cart\Models\ShoppingCart;
use Modules\Cart\Services\CartService;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ShoppingCart $cart */
        $cart = $this->resource;

        return app(CartService::class)->payload($cart);
    }
}

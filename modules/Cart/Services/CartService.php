<?php

declare(strict_types=1);

namespace Modules\Cart\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\ShoppingCart;
use Modules\Cart\Models\ShoppingCartItem;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class CartService
{
    public function __construct(
        private readonly CartSessionService $cartSessionService,
        private readonly FlashSaleService $flashSaleService,
    ) {
    }

    public function getOrCreateActiveCart(?User $user = null): ShoppingCart
    {
        if ($user !== null) {
            /** @var ShoppingCart $cart */
            $cart = ShoppingCart::query()->firstOrCreate(
                ['user_id' => $user->id, 'status' => ShoppingCart::STATUS_ACTIVE],
                ['guest_token' => null],
            );

            return $cart->load(['items.product', 'items.sku.attributes']);
        }

        $token = $this->cartSessionService->ensureGuestToken();

        /** @var ShoppingCart $cart */
        $cart = ShoppingCart::query()->firstOrCreate(
            ['guest_token' => $token, 'status' => ShoppingCart::STATUS_ACTIVE],
            ['user_id' => null],
        );

        return $cart->load(['items.product', 'items.sku.attributes']);
    }

    public function addItem(array $payload, ?User $user = null): ShoppingCart
    {
        return DB::transaction(function () use ($payload, $user): ShoppingCart {
            $cart = $this->getOrCreateActiveCart($user);
            $sku = $this->resolvePurchasableSku((int) $payload['product_sku_id']);
            $quantity = max(1, (int) $payload['quantity']);

            $item = $cart->items()->where('product_sku_id', $sku->id)->first();
            $nextQuantity = ($item?->quantity ?? 0) + $quantity;
            $this->assertStock($sku, $nextQuantity);

            $cart->items()->updateOrCreate(
                ['product_sku_id' => $sku->id],
                [
                    'product_id' => $sku->product_id,
                    'quantity' => $nextQuantity,
                    'product_name' => $sku->product->name,
                    'sku_name' => $sku->name,
                    'unit_price' => $this->flashSaleService->effectivePrice($sku),
                    'compare_price' => $this->resolveComparePrice($sku),
                ],
            );

            return $cart->fresh(['items.product', 'items.sku.attributes']);
        });
    }

    public function updateItem(ShoppingCartItem $item, int $quantity): ShoppingCart
    {
        $quantity = max(1, $quantity);
        $sku = $this->resolvePurchasableSku($item->product_sku_id);
        $this->assertStock($sku, $quantity);

        $item->forceFill([
            'quantity' => $quantity,
            'unit_price' => $this->flashSaleService->effectivePrice($sku),
            'compare_price' => $this->resolveComparePrice($sku),
        ])->save();

        return $item->cart->fresh(['items.product', 'items.sku.attributes']);
    }

    public function removeItem(ShoppingCartItem $item): ShoppingCart
    {
        $cart = $item->cart;
        $item->delete();

        return $cart->fresh(['items.product', 'items.sku.attributes']);
    }

    public function resolveItemForCurrentContext(int $itemId, ?User $user = null): ShoppingCartItem
    {
        $cart = $this->getOrCreateActiveCart($user);

        /** @var ShoppingCartItem $item */
        $item = $cart->items()->findOrFail($itemId);

        return $item;
    }

    public function mergeGuestCartIntoUser(User $user): ShoppingCart
    {
        $guestToken = $this->cartSessionService->currentGuestToken();
        $accountCart = $this->getOrCreateActiveCart($user);

        if ($guestToken === null) {
            return $accountCart;
        }

        $guestCart = ShoppingCart::query()
            ->where('guest_token', $guestToken)
            ->where('status', ShoppingCart::STATUS_ACTIVE)
            ->with('items')
            ->first();

        if ($guestCart === null || $guestCart->id === $accountCart->id) {
            return $accountCart;
        }

        DB::transaction(function () use ($guestCart, $accountCart): void {
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $accountCart->items()->where('product_sku_id', $guestItem->product_sku_id)->first();
                $mergedQuantity = ($existingItem?->quantity ?? 0) + $guestItem->quantity;

                $accountCart->items()->updateOrCreate(
                    ['product_sku_id' => $guestItem->product_sku_id],
                    [
                        'product_id' => $guestItem->product_id,
                        'quantity' => $mergedQuantity,
                        'product_name' => $guestItem->product_name,
                        'sku_name' => $guestItem->sku_name,
                        'unit_price' => $guestItem->unit_price,
                        'compare_price' => $guestItem->compare_price,
                    ],
                );
            }

            $guestCart->forceFill(['status' => ShoppingCart::STATUS_MERGED])->save();
        });

        return $accountCart->fresh(['items.product', 'items.sku.attributes']);
    }

    public function payload(ShoppingCart $cart): array
    {
        // Batch-preload active flash sale items for all SKUs in this cart to eliminate N+1
        $skuIds = $cart->items->pluck('product_sku_id')->filter()->unique()->values()->all();
        $flashSaleItemsBySkuId = [];

        if ($skuIds !== []) {
            $activeItems = \Modules\FlashSale\Models\FlashSaleItem::query()
                ->with('flashSale')
                ->whereIn('product_sku_id', $skuIds)
                ->whereHas('flashSale', fn ($q) => $q
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now()))
                ->get()
                ->keyBy('product_sku_id');

            foreach ($activeItems as $skuId => $flashItem) {
                // Only include if not sold out
                if ($flashItem->quantity_limit === null || $flashItem->sold_quantity < $flashItem->quantity_limit) {
                    $flashSaleItemsBySkuId[$skuId] = $flashItem;
                }
            }
        }

        $items = $cart->items->map(function (ShoppingCartItem $item) use ($flashSaleItemsBySkuId): array {
            $sku = $item->sku;
            $flashItem = $sku instanceof ProductSku ? ($flashSaleItemsBySkuId[$sku->id] ?? null) : null;

            $unitPrice = $flashItem !== null
                ? (float) $flashItem->sale_price
                : ($sku instanceof ProductSku ? (float) $sku->price : (float) $item->unit_price);

            $comparePrice = $flashItem !== null
                ? max((float) $sku->price, (float) ($sku->compare_price ?? 0))
                : ($sku instanceof ProductSku ? $this->resolveComparePrice($sku) : ($item->compare_price !== null ? (float) $item->compare_price : null));

            $flashSaleContext = $flashItem !== null && $flashItem->flashSale ? [
                'item_id' => $flashItem->id,
                'flash_sale_id' => $flashItem->flash_sale_id,
                'title' => $flashItem->flashSale->title,
                'sale_price' => (float) $flashItem->sale_price,
                'quantity_limit' => $flashItem->quantity_limit,
                'sold_quantity' => (int) $flashItem->sold_quantity,
                'remaining_quantity' => $flashItem->quantity_limit !== null
                    ? max(0, (int) $flashItem->quantity_limit - (int) $flashItem->sold_quantity)
                    : null,
                'starts_at' => $flashItem->flashSale->starts_at?->toISOString(),
                'ends_at' => $flashItem->flashSale->ends_at?->toISOString(),
            ] : null;

            $lineTotal = $unitPrice * $item->quantity;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_sku_id' => $item->product_sku_id,
                'product_name' => $item->product_name,
                'sku_name' => $item->sku_name,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'unit_weight' => $sku?->weight !== null ? (float) $sku->weight : 0.0,
                'compare_price' => $comparePrice,
                'line_total' => $lineTotal,
                'flash_sale' => $flashSaleContext,
                'attributes' => $sku?->attributes?->map(fn ($attribute): array => [
                    'attribute_name' => $attribute->attribute_name,
                    'attribute_value' => $attribute->attribute_value,
                ])->values()->all() ?? [],
                'product' => $item->product ? [
                    'slug' => $item->product->slug,
                    'name' => $item->product->name,
                ] : null,
            ];
        })->values();

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'items' => $items->all(),
            'total_quantity' => (int) $items->sum('quantity'),
            'subtotal' => (float) $items->sum('line_total'),
            'grand_total' => (float) $items->sum('line_total'),
        ];
    }

    public function activePayload(?User $user = null): array
    {
        return $this->payload($this->getOrCreateActiveCart($user));
    }

    public function previewBuyNow(int $skuId, int $quantity): array
    {
        $sku = $this->resolvePurchasableSku($skuId);
        $quantity = max(1, $quantity);
        $this->assertStock($sku, $quantity);
        $effectivePrice = $this->flashSaleService->effectivePrice($sku);
        $flashSale = $this->flashSaleService->saleContextForSku($sku);

        return [
            'mode' => 'buy_now',
            'items' => [[
                'product_id' => $sku->product_id,
                'product_sku_id' => $sku->id,
                'product_name' => $sku->product->name,
                'sku_name' => $sku->name,
                'quantity' => $quantity,
                'unit_price' => $effectivePrice,
                'unit_weight' => $sku->weight !== null ? (float) $sku->weight : 0.0,
                'compare_price' => $this->resolveComparePrice($sku),
                'line_total' => $effectivePrice * $quantity,
                'flash_sale' => $flashSale,
                'attributes' => $sku->attributes->map(fn ($attribute): array => [
                    'attribute_name' => $attribute->attribute_name,
                    'attribute_value' => $attribute->attribute_value,
                ])->values()->all(),
                'product' => [
                    'slug' => $sku->product->slug,
                    'name' => $sku->product->name,
                ],
            ]],
            'total_quantity' => $quantity,
            'subtotal' => $effectivePrice * $quantity,
            'grand_total' => $effectivePrice * $quantity,
        ];
    }

    private function resolvePurchasableSku(int $skuId): ProductSku
    {
        /** @var ProductSku $sku */
        $sku = ProductSku::query()
            ->with(['product', 'attributes'])
            ->where('status', ProductSku::STATUS_ACTIVE)
            ->findOrFail($skuId);

        abort_unless($sku->product instanceof Product && $sku->product->status === Product::STATUS_ACTIVE, 422, __('frontend.cart.messages.product_unavailable'));

        return $sku;
    }

    private function assertStock(ProductSku $sku, int $quantity): void
    {
        abort_if($quantity > (int) $sku->stock_quantity, 422, __('frontend.cart.messages.stock_exceeded'));
        $this->flashSaleService->assertItemQuantity($sku, $quantity);
    }

    private function resolveComparePrice(ProductSku $sku): ?float
    {
        $effectivePrice = $this->flashSaleService->effectivePrice($sku);
        $baseComparePrice = $sku->compare_price !== null ? (float) $sku->compare_price : null;

        if ($effectivePrice < (float) $sku->price) {
            return max((float) $sku->price, (float) ($baseComparePrice ?? 0));
        }

        return $baseComparePrice;
    }
}

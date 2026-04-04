<?php

declare(strict_types=1);

namespace Modules\Wishlist\Services;

use App\Models\User;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use Modules\Wishlist\Models\Wishlist;

class WishlistService
{
    public function toggle(User $user, Product $product): bool
    {
        /** @var Wishlist|null $existing */
        $existing = Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing instanceof Wishlist) {
            $existing->delete();

            return false;
        }

        Wishlist::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        return Wishlist::query()
            ->with(['product.category', 'product.skus.attributes', 'product.media'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get()
            ->map(fn (Wishlist $wishlist): ?array => $wishlist->product ? (new ProductResource($wishlist->product))->resolve() : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topProducts(): array
    {
        return Product::query()
            ->with(['category', 'skus.attributes', 'media'])
            ->withCount('wishlists')
            ->orderByDesc('wishlists_count')
            ->limit(20)
            ->get()
            ->map(fn (Product $product): array => array_replace(
                (new ProductResource($product))->resolve(),
                ['wishlists_count' => (int) ($product->wishlists_count ?? 0)],
            ))
            ->all();
    }
}

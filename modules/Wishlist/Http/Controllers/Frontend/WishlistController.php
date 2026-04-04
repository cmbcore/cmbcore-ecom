<?php

declare(strict_types=1);

namespace Modules\Wishlist\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Product\Models\Product;
use Modules\Wishlist\Services\WishlistService;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {
    }

    public function index(): View
    {
        $products = $this->wishlistService->listForUser(request()->user());

        return theme_manager()->view('account.wishlist', [
            'page' => [
                'title' => theme_text('account.wishlist_title'),
                'meta_title' => theme_text('account.wishlist_title'),
                'meta_description' => theme_text('account.wishlist_description'),
            ],
            'breadcrumbs' => [
                ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                ['label' => theme_text('account.wishlist_title'), 'url' => route('storefront.wishlist.index')],
            ],
            'products' => $products,
            'wishlist_count' => count($products),
        ]);
    }

    public function toggle(string $slug): RedirectResponse
    {
        /** @var Product $product */
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $added = $this->wishlistService->toggle(request()->user(), $product);

        return back()->with('status', $added
            ? __('frontend.account.messages.wishlist_added')
            : __('frontend.account.messages.wishlist_removed'));
    }
}

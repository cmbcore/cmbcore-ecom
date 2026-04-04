<?php

declare(strict_types=1);

namespace Modules\Wishlist\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product;
use Modules\Wishlist\Services\WishlistService;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->listForUser(request()->user()),
        ]);
    }

    public function toggle(int $productId): JsonResponse
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($productId);
        $added = $this->wishlistService->toggle(request()->user(), $product);

        return response()->json([
            'success' => true,
            'data' => ['added' => $added],
            'message' => $added ? 'Da them vao wishlist.' : 'Da xoa khoi wishlist.',
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->topProducts(),
        ]);
    }
}

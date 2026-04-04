<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Resources\ProductSkuResource;
use Modules\Product\Models\Product;

class ProductSkuController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProductSkuResource::collection($product->skus()->with('attributes')->get())->resolve(),
            'message' => __('admin.products.messages.skus_loaded'),
        ]);
    }
}

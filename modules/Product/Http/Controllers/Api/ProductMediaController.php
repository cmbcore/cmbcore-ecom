<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Resources\ProductMediaResource;
use Modules\Product\Models\Product;

class ProductMediaController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProductMediaResource::collection($product->media()->get())->resolve(),
            'message' => __('admin.products.messages.media_loaded'),
        ]);
    }
}

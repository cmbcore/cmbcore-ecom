<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products->getCollection())->resolve(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'message' => __('admin.products.messages.list_loaded'),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->validated(),
            (array) $request->file('uploads', []),
        );

        return response()->json([
            'success' => true,
            'data' => (new ProductResource($product))->resolve(),
            'message' => __('admin.products.messages.created'),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new ProductResource($product->load(['category', 'skus.attributes', 'media'])))->resolve(),
            'message' => __('admin.products.messages.detail_loaded'),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update(
            $product,
            $request->validated(),
            (array) $request->file('uploads', []),
        );

        return response()->json([
            'success' => true,
            'data' => (new ProductResource($product))->resolve(),
            'message' => __('admin.products.messages.updated'),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => __('admin.products.messages.deleted'),
        ]);
    }
}

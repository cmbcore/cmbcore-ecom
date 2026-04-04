<?php

declare(strict_types=1);

namespace Modules\Category\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Category\Http\Requests\StoreCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Category\Models\Category;
use Modules\Category\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ImageUploadService $imageUploadService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories->getCollection())->resolve(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
            'message' => __('admin.categories.messages.list_loaded'),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $tree = $this->categoryService->getTree(
            $request->filled('exclude_id') ? (int) $request->integer('exclude_id') : null,
        );

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($tree)->resolve(),
            'message' => __('admin.categories.messages.tree_loaded'),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->imageUploadService->store(
                $request->file('image_file'),
                'categories',
            );
        }

        $category = $this->categoryService->create($data);

        return response()->json([
            'success' => true,
            'data' => (new CategoryResource($category->load('parent')))->resolve(),
            'message' => __('admin.categories.messages.created'),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new CategoryResource($category->load(['parent', 'children'])))->resolve(),
            'message' => __('admin.categories.messages.detail_loaded'),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $this->imageUploadService->delete($category->image);

            $data['image'] = $this->imageUploadService->store(
                $request->file('image_file'),
                'categories',
            );
        } elseif (array_key_exists('image', $data) && ($data['image'] === null || $data['image'] === '')) {
            $this->imageUploadService->delete($category->image);
            $data['image'] = null;
        }

        $category = $this->categoryService->update($category, $data);

        return response()->json([
            'success' => true,
            'data' => (new CategoryResource($category->load(['parent', 'children'])))->resolve(),
            'message' => __('admin.categories.messages.updated'),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => __('admin.categories.messages.deleted'),
        ]);
    }
}

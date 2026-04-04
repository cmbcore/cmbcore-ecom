<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\StoreBlogCategoryRequest;
use Modules\Blog\Http\Requests\UpdateBlogCategoryRequest;
use Modules\Blog\Http\Resources\BlogCategoryResource;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Services\BlogCategoryService;

class BlogCategoryController extends Controller
{
    public function __construct(
        private readonly BlogCategoryService $categoryService,
        private readonly ImageUploadService $imageUploadService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => BlogCategoryResource::collection($categories->getCollection())->resolve(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
            'message' => __('admin.blog_categories.messages.list_loaded'),
        ]);
    }

    public function store(StoreBlogCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->imageUploadService->store(
                $request->file('image_file'),
                'blog-categories',
            );
        }

        $category = $this->categoryService->create($data);

        return response()->json([
            'success' => true,
            'data' => (new BlogCategoryResource($category))->resolve(),
            'message' => __('admin.blog_categories.messages.created'),
        ], 201);
    }

    public function show(BlogCategory $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new BlogCategoryResource($category))->resolve(),
            'message' => __('admin.blog_categories.messages.detail_loaded'),
        ]);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_file')) {
            $this->imageUploadService->delete($category->image);

            $data['image'] = $this->imageUploadService->store(
                $request->file('image_file'),
                'blog-categories',
            );
        } elseif (array_key_exists('image', $data) && ($data['image'] === null || $data['image'] === '')) {
            $this->imageUploadService->delete($category->image);
            $data['image'] = null;
        }

        $category = $this->categoryService->update($category, $data);

        return response()->json([
            'success' => true,
            'data' => (new BlogCategoryResource($category))->resolve(),
            'message' => __('admin.blog_categories.messages.updated'),
        ]);
    }

    public function destroy(BlogCategory $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => __('admin.blog_categories.messages.deleted'),
        ]);
    }
}

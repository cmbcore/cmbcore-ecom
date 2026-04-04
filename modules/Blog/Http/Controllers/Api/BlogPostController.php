<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\StoreBlogPostRequest;
use Modules\Blog\Http\Requests\UpdateBlogPostRequest;
use Modules\Blog\Http\Resources\BlogPostResource;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Services\BlogService;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly BlogService $blogService,
        private readonly ImageUploadService $imageUploadService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $posts = $this->blogService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => BlogPostResource::collection($posts->getCollection())->resolve(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
            'message' => __('admin.blogs.messages.list_loaded'),
        ]);
    }

    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image_file')) {
            $data['featured_image'] = $this->imageUploadService->store(
                $request->file('featured_image_file'),
                'blog',
            );
        }

        $post = $this->blogService->create($data);

        return response()->json([
            'success' => true,
            'data' => (new BlogPostResource($post))->resolve(),
            'message' => __('admin.blogs.messages.created'),
        ], 201);
    }

    public function show(BlogPost $post): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new BlogPostResource($post->load('category')))->resolve(),
            'message' => __('admin.blogs.messages.detail_loaded'),
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image_file')) {
            // Delete old image if it is a locally stored file
            $this->imageUploadService->delete($post->featured_image);

            $data['featured_image'] = $this->imageUploadService->store(
                $request->file('featured_image_file'),
                'blog',
            );
        } elseif (array_key_exists('featured_image', $data) && ($data['featured_image'] === null || $data['featured_image'] === '')) {
            $this->imageUploadService->delete($post->featured_image);
            $data['featured_image'] = null;
        }

        $post = $this->blogService->update($post, $data);

        return response()->json([
            'success' => true,
            'data' => (new BlogPostResource($post))->resolve(),
            'message' => __('admin.blogs.messages.updated'),
        ]);
    }

    public function destroy(BlogPost $post): JsonResponse
    {
        $this->blogService->delete($post);

        return response()->json([
            'success' => true,
            'message' => __('admin.blogs.messages.deleted'),
        ]);
    }
}

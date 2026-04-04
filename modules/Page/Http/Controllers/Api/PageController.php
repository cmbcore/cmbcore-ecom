<?php

declare(strict_types=1);

namespace Modules\Page\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use App\Services\PageShortcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Page\Http\Requests\StorePageRequest;
use Modules\Page\Http\Requests\UpdatePageRequest;
use Modules\Page\Http\Resources\PageResource;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageService;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly ImageUploadService $imageUploadService,
        private readonly PageShortcodeService $pageShortcodeService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $pages = $this->pageService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => PageResource::collection($pages->getCollection())->resolve(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
            'message' => __('admin.pages.messages.list_loaded'),
        ]);
    }

    public function templates(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'templates' => $this->pageService->templates(),
                'blocks' => $this->pageShortcodeService->definitions(),
            ],
            'message' => __('admin.pages.messages.templates_loaded'),
        ]);
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $data = $this->resolvePayload($request, $request->validated());

        if ($request->hasFile('featured_image_file')) {
            $data['featured_image'] = $this->imageUploadService->store(
                $request->file('featured_image_file'),
                'pages',
            );
        }

        $page = $this->pageService->create($data);

        return response()->json([
            'success' => true,
            'data' => (new PageResource($page))->resolve(),
            'message' => __('admin.pages.messages.created'),
        ], 201);
    }

    public function show(Page $page): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new PageResource($page))->resolve(),
            'message' => __('admin.pages.messages.detail_loaded'),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $data = $this->resolvePayload($request, $request->validated());

        if ($request->hasFile('featured_image_file')) {
            $this->imageUploadService->delete($page->featured_image);

            $data['featured_image'] = $this->imageUploadService->store(
                $request->file('featured_image_file'),
                'pages',
            );
        } elseif (array_key_exists('featured_image', $data) && ($data['featured_image'] === null || $data['featured_image'] === '')) {
            $this->imageUploadService->delete($page->featured_image);
            $data['featured_image'] = null;
        }

        $page = $this->pageService->update($page, $data);

        return response()->json([
            'success' => true,
            'data' => (new PageResource($page))->resolve(),
            'message' => __('admin.pages.messages.updated'),
        ]);
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->pageService->delete($page);

        return response()->json([
            'success' => true,
            'message' => __('admin.pages.messages.deleted'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolvePayload(Request $request, array $validated): array
    {
        $validated['content_blocks'] = $this->decodeArrayInput($request->input('content_blocks', $validated['content_blocks'] ?? []));

        return array_replace($validated, [
            'content_blocks' => $this->resolveBlockMedia(
                is_array($validated['content_blocks']) ? $validated['content_blocks'] : [],
                (array) $request->file('uploads', []),
            ),
        ]);
    }

    /**
     * @param  array<string, UploadedFile>  $uploads
     * @return array<int, array<string, mixed>>
     */
    private function resolveBlockMedia(array $blocks, array $uploads): array
    {
        return array_map(function (array $block) use ($uploads): array {
            $props = $this->resolveMediaValue((array) ($block['props'] ?? []), $uploads);

            return array_replace($block, ['props' => is_array($props) ? $props : []]);
        }, $blocks);
    }

    /**
     * @param  array<string, UploadedFile>  $uploads
     */
    private function resolveMediaValue(mixed $value, array $uploads): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (isset($value['upload_token'])) {
            $token = (string) $value['upload_token'];

            return isset($uploads[$token])
                ? $this->imageUploadService->store($uploads[$token], 'pages/blocks')
                : null;
        }

        return array_map(fn (mixed $item): mixed => $this->resolveMediaValue($item, $uploads), $value);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeArrayInput(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

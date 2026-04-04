<?php

declare(strict_types=1);

namespace Modules\Banner\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Banner\Services\BannerService;

class BannerController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bannerService->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:banners,id'],
            'title' => ['required', 'string', 'max:255'],
            'desktop_image' => ['required', 'string', 'max:500'],
            'mobile_image' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'position' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->bannerService->save($payload),
            'message' => 'Da luu banner.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->bannerService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa banner.',
        ]);
    }
}

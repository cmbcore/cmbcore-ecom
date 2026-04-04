<?php

declare(strict_types=1);

namespace Modules\MediaLibrary\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MediaLibrary\Services\MediaLibraryService;

class MediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibraryService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->mediaLibraryService->list(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'folder' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->mediaLibraryService->upload($request->file('file'), (string) ($payload['folder'] ?? 'uploads')),
            'message' => 'Da tai file len media library.',
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->mediaLibraryService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa file khoi media library.',
        ]);
    }
}

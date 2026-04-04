<?php

declare(strict_types=1);

namespace Modules\Search\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {
    }

    public function suggestions(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->searchService->suggestions((string) $request->query('q', '')),
        ]);
    }

    public function analytics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->searchService->analytics(),
        ]);
    }
}

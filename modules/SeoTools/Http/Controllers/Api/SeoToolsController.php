<?php

declare(strict_types=1);

namespace Modules\SeoTools\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\SeoTools\Services\SeoToolsService;

class SeoToolsController extends Controller
{
    public function __construct(
        private readonly SeoToolsService $seoToolsService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->seoToolsService->overview(),
        ]);
    }
}

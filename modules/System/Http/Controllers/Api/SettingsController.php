<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api;

use App\Core\Plugin\HookManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\Services\SettingsAdminService;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsAdminService $settingsAdminService,
        private readonly HookManager $hookManager,
    ) {
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->settingsAdminService->payload(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $this->settingsAdminService->update($request->all());
        $this->hookManager->fire('system.settings.updated', $payload);

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => 'Da cap nhat cai dat he thong.',
        ]);
    }
}

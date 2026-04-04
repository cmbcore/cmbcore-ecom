<?php

declare(strict_types=1);

namespace Modules\PluginManager\Http\Controllers\Api;

use App\Core\Plugin\PluginManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function __construct(
        private readonly PluginManager $pluginManager,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->payloads(),
            'message' => __('admin.plugins.messages.list_loaded'),
        ]);
    }

    public function settings(string $alias): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->configuration($alias),
            'message' => __('admin.plugins.messages.settings_loaded'),
        ]);
    }

    public function install(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'],
            'force' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->installFromArchive(
                $payload['package'],
                (bool) ($payload['force'] ?? false),
            ),
            'message' => __('admin.plugins.messages.installed'),
        ], 201);
    }

    public function updateSettings(Request $request, string $alias): JsonResponse
    {
        $payload = $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->updateConfiguration(
                $alias,
                (array) ($payload['settings'] ?? []),
            ),
            'message' => __('admin.plugins.messages.updated'),
        ]);
    }

    public function enable(string $alias): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->enable($alias),
            'message' => __('admin.plugins.messages.enabled'),
        ]);
    }

    public function disable(string $alias): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->pluginManager->disable($alias),
            'message' => __('admin.plugins.messages.disabled'),
        ]);
    }

    public function destroy(string $alias): JsonResponse
    {
        $this->pluginManager->delete($alias);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa plugin.',
        ]);
    }
}

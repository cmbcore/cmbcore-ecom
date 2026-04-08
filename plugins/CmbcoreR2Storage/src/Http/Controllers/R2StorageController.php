<?php

declare(strict_types=1);

namespace Plugins\CmbcoreR2Storage\Http\Controllers;

use App\Core\Plugin\PluginManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\CmbcoreR2Storage\CmbcoreR2StoragePlugin;

class R2StorageController extends Controller
{
    private function plugin(): CmbcoreR2StoragePlugin
    {
        return app(CmbcoreR2StoragePlugin::class);
    }

    private function manager(): PluginManager
    {
        return app(PluginManager::class);
    }

    /**
     * GET /api/admin/plugins/cmbcore-r2-storage/settings
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->manager()->configuration('cmbcore-r2-storage'),
        ]);
    }

    /**
     * PUT /api/admin/plugins/cmbcore-r2-storage/settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $input = $request->validate([
            'account_id'        => ['nullable', 'string', 'max:255'],
            'access_key_id'     => ['nullable', 'string', 'max:255'],
            'secret_access_key' => ['nullable', 'string', 'max:255'],
            'bucket'            => ['nullable', 'string', 'max:255'],
            'region'            => ['nullable', 'string', 'max:50'],
            'public_url'        => ['nullable', 'string', 'url', 'max:500'],
            'upload_folder'     => ['nullable', 'string', 'max:255'],
            'visibility'        => ['nullable', 'string', 'in:public,private'],
        ]);

        $configuration = $this->manager()->updateConfiguration('cmbcore-r2-storage', $input);

        return response()->json([
            'success' => true,
            'data'    => $configuration,
            'message' => 'Đã lưu cấu hình R2 Storage.',
        ]);
    }

    /**
     * POST /api/admin/plugins/cmbcore-r2-storage/test-connection
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->plugin()->testConnection();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    /**
     * GET /api/admin/plugins/cmbcore-r2-storage/status
     */
    public function status(): JsonResponse
    {
        $settings     = $this->plugin()->settings();
        $activeDisk   = config('media_library.disk', 'public');
        $hasCredentials = array_reduce(
            ['account_id', 'access_key_id', 'secret_access_key', 'bucket'],
            fn (bool $carry, string $key): bool => $carry && trim((string) ($settings[$key] ?? '')) !== '',
            true,
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'active_disk'     => $activeDisk,
                'r2_active'       => $activeDisk === 'r2',
                'has_credentials' => $hasCredentials,
                'public_url'      => $settings['public_url'] ?? '',
                'bucket'          => $settings['bucket'] ?? '',
            ],
        ]);
    }
}

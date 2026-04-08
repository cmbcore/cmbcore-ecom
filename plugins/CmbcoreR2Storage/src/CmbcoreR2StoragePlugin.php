<?php

declare(strict_types=1);

namespace Plugins\CmbcoreR2Storage;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use App\Models\InstalledPlugin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class CmbcoreR2StoragePlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
        $settings = $this->settings();

        // ── Load controller class (no PSR-4 for Plugins\ namespace) ────
        require_once __DIR__ . '/Http/Controllers/R2StorageController.php';

        // ── Register custom admin API routes (test-connection, status) ──
        // NOTE: settings GET/PUT are handled by the generic PluginManager
        //       routes at api/admin/plugins/{alias}/settings
        if (! Route::has('plugin.cmbcore-r2-storage.test')) {
            Route::prefix('api/admin/plugins/cmbcore-r2-storage')
                ->middleware(['api', 'auth:sanctum', 'admin'])
                ->name('plugin.cmbcore-r2-storage.')
                ->group(function (): void {
                    Route::post('/test-connection', [\Plugins\CmbcoreR2Storage\Http\Controllers\R2StorageController::class, 'testConnection'])->name('test');
                    Route::get('/status',           [\Plugins\CmbcoreR2Storage\Http\Controllers\R2StorageController::class, 'status'])->name('status');
                });
        }

        // ── Only configure disk if credentials are present ──────────────
        if (! $this->hasRequiredSettings($settings)) {
            return;
        }

        try {
            $this->registerR2Disk($settings);
        } catch (\Throwable $e) {
            Log::warning('[R2Storage] Failed to register R2 disk.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function activate(): void
    {
        // ── Validate required settings before allowing activation ─────────
        // This runs inside a DB transaction; throwing here rolls back is_active=true
        $settings = $this->settings();
        $errors   = [];

        $requiredFields = [
            'account_id'        => 'Account ID (Cloudflare)',
            'access_key_id'     => 'Access Key ID',
            'secret_access_key' => 'Secret Access Key',
            'bucket'            => 'Bucket Name',
        ];

        foreach ($requiredFields as $key => $label) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                $errors[$key] = ["Vui lòng nhập {$label} trong phần cấu hình plugin trước khi bật."];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(array_merge(
                ['plugin' => ['Plugin R2 Storage chưa được cấu hình. Vui lòng nhập đầy đủ thông tin kết nối R2 trước khi bật plugin.']],
                $errors,
            ));
        }

        // Validate URL format for public_url if provided
        $publicUrl = trim((string) ($settings['public_url'] ?? ''));
        if ($publicUrl !== '' && ! filter_var($publicUrl, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'public_url' => ['Public URL không đúng định dạng (phải bắt đầu bằng https://).'],
            ]);
        }
    }

    public function deactivate(): void
    {
        // Revert media_library.disk to 'public' for safety
        Config::set('media_library.disk', 'public');
    }

    public function uninstall(): void
    {
        Config::set('media_library.disk', 'public');
    }

    // ──────────────────────────────────────────────────────────────────
    // Public API used by controller
    // ──────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $plugin = InstalledPlugin::query()->where('alias', 'cmbcore-r2-storage')->first();

        return is_array($plugin?->settings) ? $plugin->settings : [];
    }

    /**
     * Test the R2 connection using current settings.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        $settings = $this->settings();

        if (! $this->hasRequiredSettings($settings)) {
            return ['success' => false, 'message' => 'Chưa cấu hình đủ thông tin kết nối R2.'];
        }

        try {
            $this->registerR2Disk($settings);
            \Illuminate\Support\Facades\Storage::disk('r2')->files('');
            return ['success' => true, 'message' => 'Kết nối R2 thành công!'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Kết nối thất bại: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Dynamically register the R2 disk and set it as the active media disk.
     *
     * @param array<string, mixed> $settings
     */
    private function registerR2Disk(array $settings): void
    {
        $accountId  = trim((string) ($settings['account_id'] ?? ''));
        $accessKey  = trim((string) ($settings['access_key_id'] ?? ''));
        $secret     = trim((string) ($settings['secret_access_key'] ?? ''));
        $bucket     = trim((string) ($settings['bucket'] ?? ''));
        $region     = trim((string) ($settings['region'] ?? 'auto'));
        $publicUrl  = rtrim(trim((string) ($settings['public_url'] ?? '')), '/');
        $visibility = in_array($settings['visibility'] ?? '', ['public', 'private'], true)
            ? (string) $settings['visibility']
            : 'public';

        $endpoint = "https://{$accountId}.r2.cloudflarestorage.com";

        // Register disk configuration at runtime
        Config::set('filesystems.disks.r2', [
            'driver'                  => 's3',
            'key'                     => $accessKey,
            'secret'                  => $secret,
            'region'                  => $region,
            'bucket'                  => $bucket,
            'endpoint'                => $endpoint,
            'use_path_style_endpoint' => false,
            'url'                     => $publicUrl !== '' ? $publicUrl : null,
            'visibility'              => $visibility,
            'throw'                   => false,
        ]);

        // Force Storage to forget any cached instance so it picks up new config
        \Illuminate\Support\Facades\Storage::forgetDisk('r2');

        // Override media library to use R2
        Config::set('media_library.disk', 'r2');
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function hasRequiredSettings(array $settings): bool
    {
        foreach (['account_id', 'access_key_id', 'secret_access_key', 'bucket'] as $key) {
            if (trim((string) ($settings[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers\Api;

use App\Core\Plugin\HookManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Modules\System\Services\SettingsAdminService;
use Throwable;

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
            'message' => 'Đã cập nhật cài đặt hệ thống.',
        ]);
    }

    /**
     * POST /api/admin/system/settings/test-email
     * Gửi email kiểm tra với cấu hình SMTP từ request (chưa cần lưu).
     */
    public function testEmail(Request $request): JsonResponse
    {
        $config = $request->validate([
            'mail_mailer' => ['nullable', 'string'],
            'mail_host' => ['nullable', 'string'],
            'mail_port' => ['nullable', 'integer'],
            'mail_encryption' => ['nullable', 'string'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_from_name' => ['nullable', 'string'],
            'mail_from_address' => ['nullable', 'string', 'email'],
        ]);

        $to = $config['mail_from_address'] ?? null;

        if (! $to) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp địa chỉ email người gửi.',
            ], 422);
        }

        // Override config temporarily for this request only
        Config::set('mail.default', $config['mail_mailer'] ?? config('mail.default'));
        Config::set('mail.mailers.smtp.host', $config['mail_host'] ?? config('mail.mailers.smtp.host'));
        Config::set('mail.mailers.smtp.port', $config['mail_port'] ?? config('mail.mailers.smtp.port'));
        Config::set('mail.mailers.smtp.encryption', $config['mail_encryption'] ?? config('mail.mailers.smtp.encryption'));
        Config::set('mail.mailers.smtp.username', $config['mail_username'] ?? config('mail.mailers.smtp.username'));
        Config::set('mail.mailers.smtp.password', $config['mail_password'] ?? config('mail.mailers.smtp.password'));
        Config::set('mail.from.address', $to);
        Config::set('mail.from.name', $config['mail_from_name'] ?? config('mail.from.name'));

        try {
            Mail::raw(
                "Đây là email kiểm tra từ hệ thống CMBCORE.\n\nNếu bạn nhận được email này, cấu hình SMTP đang hoạt động bình thường.",
                function ($message) use ($to, $config): void {
                    $message
                        ->to($to)
                        ->subject('[CMBCORE] Kiểm tra kết nối email - ' . now()->format('d/m/Y H:i'));
                },
            );

            return response()->json([
                'success' => true,
                'message' => "Email kiểm tra đã được gửi đến {$to}.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gửi email thất bại: ' . $e->getMessage(),
            ], 422);
        }
    }
}

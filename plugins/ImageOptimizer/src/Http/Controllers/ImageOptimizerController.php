<?php

declare(strict_types=1);

namespace Plugins\ImageOptimizer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Plugins\ImageOptimizer\ImageOptimizerPlugin;

class ImageOptimizerController extends Controller
{
    public function __construct(private readonly ImageOptimizerPlugin $plugin) {}

    /**
     * GET /api/admin/plugins/image-optimizer/settings
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'data'     => [
                'settings'       => $this->plugin->settings(),
                'gd_available'   => function_exists('imagecreatefromjpeg'),
                'webp_available' => function_exists('imagewebp'),
            ],
        ]);
    }

    /**
     * PUT /api/admin/plugins/image-optimizer/settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $settings = $request->validate([
            'enabled'         => ['boolean'],
            'quality'         => ['integer', 'min:10', 'max:100'],
            'max_width'       => ['integer', 'min:0'],
            'max_height'      => ['integer', 'min:0'],
            'keep_original'   => ['boolean'],
            'wm_enabled'      => ['boolean'],
            'wm_type'         => ['string', 'in:text,image'],
            'wm_text'         => ['nullable', 'string', 'max:200'],
            'wm_text_size'    => ['integer', 'min:8', 'max:120'],
            'wm_text_color'   => ['nullable', 'string', 'max:20'],
            'wm_opacity'      => ['integer', 'min:0', 'max:100'],
            'wm_image'        => ['nullable', 'string'],
            'wm_image_width'  => ['integer', 'min:20', 'max:800'],
            'wm_position'     => ['string'],
            'wm_padding'      => ['integer', 'min:0', 'max:200'],
        ]);

        $plugin = \App\Models\InstalledPlugin::query()
            ->where('alias', 'image-optimizer')
            ->firstOrFail();

        $plugin->forceFill(['settings' => array_replace(
            $this->plugin->settings(),
            $settings,
        )])->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu cài đặt.',
            'data'    => ['settings' => $this->plugin->settings()],
        ]);
    }

    /**
     * POST /api/admin/plugins/image-optimizer/preview
     * Body: multipart/form-data with field `image` (file)
     * Returns: { success, data: { preview_url: "data:image/webp;base64,..." } }
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        try {
            $previewDataUrl = $this->plugin->generatePreview($request->file('image'));

            return response()->json([
                'success' => true,
                'data'    => ['preview_url' => $previewDataUrl],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/admin/plugins/image-optimizer/test-convert
     * Converts a single stored image to WebP as a one-off test.
     */
    public function testConvert(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = (string) $request->input('path');

        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy file.'], 404);
        }

        try {
            $newPath = $this->plugin->processImage($path);

            return response()->json([
                'success'  => true,
                'data'     => ['converted_path' => $newPath],
                'message'  => "Đã chuyển đổi thành công: {$newPath}",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

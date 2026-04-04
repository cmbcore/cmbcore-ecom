<?php

declare(strict_types=1);

namespace Plugins\ImageOptimizer;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use App\Models\InstalledPlugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Plugins\ImageOptimizer\Http\Controllers\ImageOptimizerController;

class ImageOptimizerPlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
        // ── Register admin API routes ──────────────────────────────────
        if (! Route::has('plugin.image-optimizer.settings')) {
            Route::prefix('api/admin/plugins/image-optimizer')
                ->middleware(['api', 'auth:sanctum', 'admin'])
                ->group(function (): void {
                    Route::get('/settings',      [ImageOptimizerController::class, 'settings']);
                    Route::put('/settings',      [ImageOptimizerController::class, 'saveSettings']);
                    Route::post('/preview',      [ImageOptimizerController::class, 'preview']);
                    Route::post('/test-convert', [ImageOptimizerController::class, 'testConvert']);
                });
        }

        // ── Listen to media uploads ────────────────────────────────────
        $hooks->register('media.uploaded', function (string $diskPath, string $disk = 'public'): string {
            if (! $this->isEnabled()) {
                return $diskPath;
            }

            try {
                return $this->processImage($diskPath, $disk);
            } catch (\Throwable $e) {
                Log::warning('[ImageOptimizer] Failed to process image.', [
                    'path'  => $diskPath,
                    'error' => $e->getMessage(),
                ]);
                return $diskPath;
            }
        });
    }

    public function activate(): void {}

    public function deactivate(): void {}

    public function uninstall(): void {}

    // ──────────────────────────────────────────────────────────────────
    // Public API used by the admin controller
    // ──────────────────────────────────────────────────────────────────

    /**
     * Convert & watermark an image and return the new disk-relative path.
     * Used by the admin "test convert" endpoint.
     */
    public function processImage(string $diskPath, string $disk = 'public'): string
    {
        $absolutePath = Storage::disk($disk)->path($diskPath);

        if (! file_exists($absolutePath)) {
            return $diskPath;
        }

        $settings = $this->settings();
        $quality  = (int) ($settings['quality']    ?? 82);
        $maxW     = (int) ($settings['max_width']  ?? 1920);
        $maxH     = (int) ($settings['max_height'] ?? 1920);

        // 1. Load source image
        $image = $this->loadGd($absolutePath);
        if ($image === null) {
            return $diskPath;
        }

        // 2. Resize if needed
        $image = $this->maybeResize($image, $maxW, $maxH);

        // 3. Apply watermark
        if ((bool) ($settings['wm_enabled'] ?? false)) {
            $image = $this->applyWatermark($image, $settings, $disk);
        }

        // 4. Save as WebP
        $webpPath = preg_replace('/\.(jpe?g|png|gif|bmp|tiff?)$/i', '.webp', $diskPath)
            ?? $diskPath . '.webp';

        $absoluteWebp = Storage::disk($disk)->path($webpPath);

        $this->ensureDirectory(dirname($absoluteWebp));

        imagewebp($image, $absoluteWebp, $quality);
        imagedestroy($image);

        // 5. Optionally remove original
        if (! (bool) ($settings['keep_original'] ?? false) && $webpPath !== $diskPath) {
            Storage::disk($disk)->delete($diskPath);
        }

        return $webpPath;
    }

    /**
     * Generate a watermarked preview from an uploaded test image.
     * Returns base64-encoded WebP string.
     */
    public function generatePreview(UploadedFile $file): string
    {
        $settings = $this->settings();
        $quality  = (int) ($settings['quality'] ?? 82);
        $maxW     = (int) ($settings['max_width']  ?? 1920);
        $maxH     = (int) ($settings['max_height'] ?? 1920);

        $image = $this->loadGd($file->getRealPath());
        if ($image === null) {
            throw new \RuntimeException('Không thể đọc hình ảnh.');
        }

        $image = $this->maybeResize($image, $maxW, $maxH);

        if ((bool) ($settings['wm_enabled'] ?? false)) {
            $image = $this->applyWatermark($image, $settings);
        }

        ob_start();
        imagewebp($image, null, $quality);
        $data = ob_get_clean();
        imagedestroy($image);

        return 'data:image/webp;base64,' . base64_encode((string) $data);
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * @return \GdImage|null
     */
    private function loadGd(string $path): mixed
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $mime = mime_content_type($path) ?: '';

        return match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($path),
            str_contains($mime, 'png')  => imagecreatefrompng($path),
            str_contains($mime, 'gif')  => imagecreatefromgif($path),
            str_contains($mime, 'webp') => imagecreatefromwebp($path),
            default                     => null,
        };
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function maybeResize(mixed $image, int $maxW, int $maxH): mixed
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);

        if (($maxW === 0 || $srcW <= $maxW) && ($maxH === 0 || $srcH <= $maxH)) {
            return $image;
        }

        $ratioW = $maxW > 0 ? $maxW / $srcW : PHP_FLOAT_MAX;
        $ratioH = $maxH > 0 ? $maxH / $srcH : PHP_FLOAT_MAX;
        $ratio  = min($ratioW, $ratioH, 1.0);

        $newW = (int) round($srcW * $ratio);
        $newH = (int) round($srcH * $ratio);

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Apply watermark (text or image) to a GD image resource.
     *
     * @param  \GdImage            $image
     * @param  array<string,mixed> $settings
     * @return \GdImage
     */
    private function applyWatermark(mixed $image, array $settings, string $disk = 'public'): mixed
    {
        $type     = (string)  ($settings['wm_type']    ?? 'text');
        $position = (string)  ($settings['wm_position'] ?? 'bottom-right');
        $padding  = (int)     ($settings['wm_padding']  ?? 16);
        $opacity  = (int)     ($settings['wm_opacity']  ?? 60);  // 0-100

        $imgW = imagesx($image);
        $imgH = imagesy($image);

        if ($type === 'image') {
            $wmPath = (string) ($settings['wm_image'] ?? '');
            if ($wmPath === '') {
                return $image;
            }

            $absoluteWm = Storage::disk($disk)->path(ltrim($wmPath, '/'));
            $wm = $this->loadGd($absoluteWm);

            if ($wm === null) {
                return $image;
            }

            $targetW = (int) ($settings['wm_image_width'] ?? 200);
            $srcWmW  = imagesx($wm);
            $srcWmH  = imagesy($wm);
            $targetH = $srcWmW > 0 ? (int) round($srcWmH * ($targetW / $srcWmW)) : $srcWmH;

            $resizedWm = imagecreatetruecolor($targetW, $targetH);
            imagealphablending($resizedWm, false);
            imagesavealpha($resizedWm, true);
            imagecopyresampled($resizedWm, $wm, 0, 0, 0, 0, $targetW, $targetH, $srcWmW, $srcWmH);
            imagedestroy($wm);

            [$destX, $destY] = $this->calcPosition($position, $imgW, $imgH, $targetW, $targetH, $padding);

            imagecopymerge($image, $resizedWm, $destX, $destY, 0, 0, $targetW, $targetH, $opacity);
            imagedestroy($resizedWm);

        } else {
            // Text watermark
            $text     = (string) ($settings['wm_text']       ?? '© CMBCore');
            $fontSize = (int)    ($settings['wm_text_size']  ?? 24);
            $color    = (string) ($settings['wm_text_color'] ?? '#ffffff');

            // Parse hex color
            [$r, $g, $b] = $this->hexToRgb($color);
            $alpha = (int) round((100 - $opacity) * 127 / 100);

            $textColor = imagecolorallocatealpha($image, $r, $g, $b, $alpha);

            // Try to use a bundled TTF font if available
            $fontPath = __DIR__ . '/../resources/fonts/Roboto-Bold.ttf';
            if (file_exists($fontPath) && function_exists('imagettftext')) {
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $text) ?: [0,0,0,0,0,0,0,0];
                $wmW  = abs($bbox[4] - $bbox[0]);
                $wmH  = abs($bbox[5] - $bbox[1]);
                [$destX, $destY] = $this->calcPosition($position, $imgW, $imgH, $wmW, $wmH, $padding);
                imagettftext($image, $fontSize, 0, $destX, $destY + $wmH, $textColor, $fontPath, $text);
            } else {
                // Fallback: built-in font
                $builtInFont = 5; // GD built-in font 5
                $charW = imagefontwidth($builtInFont);
                $charH = imagefontheight($builtInFont);
                $wmW   = strlen($text) * $charW;
                $wmH   = $charH;
                [$destX, $destY] = $this->calcPosition($position, $imgW, $imgH, $wmW, $wmH, $padding);
                imagestring($image, $builtInFont, $destX, $destY, $text, $textColor);
            }
        }

        return $image;
    }

    /**
     * Calculate destination X,Y for watermark based on position string.
     *
     * @return array{int,int}
     */
    private function calcPosition(string $position, int $imgW, int $imgH, int $wmW, int $wmH, int $padding): array
    {
        $x = match (true) {
            str_contains($position, 'right')  => $imgW - $wmW - $padding,
            str_contains($position, 'center') || $position === 'center' => (int) (($imgW - $wmW) / 2),
            default                           => $padding,
        };

        $y = match (true) {
            str_contains($position, 'bottom') => $imgH - $wmH - $padding,
            str_contains($position, 'center') || $position === 'center' => (int) (($imgH - $wmH) / 2),
            default                           => $padding,
        };

        return [$x, $y];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $plugin = InstalledPlugin::query()->where('alias', 'image-optimizer')->first();

        return is_array($plugin?->settings) ? $plugin->settings : [];
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? true);
    }
}

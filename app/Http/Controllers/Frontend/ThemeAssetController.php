<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Core\Theme\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeAssetController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {
    }

    /**
     * @throws FileNotFoundException
     */
    public function __invoke(string $theme, string $path): BinaryFileResponse|Response
    {
        $manifest = $this->themeManager->find($theme);

        if ($manifest === null) {
            abort(404);
        }

        $assetDirectory = realpath($manifest->getPath() . DIRECTORY_SEPARATOR . 'assets');

        if ($assetDirectory === false || ! is_dir($assetDirectory)) {
            abort(404);
        }

        $requestedAsset = realpath($assetDirectory . DIRECTORY_SEPARATOR . ltrim($path, '/'));

        if (
            $requestedAsset === false
            || ! is_file($requestedAsset)
            || ! str_starts_with($requestedAsset, $assetDirectory)
        ) {
            abort(404);
        }

        return response()->file($requestedAsset, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Type' => $this->contentTypeFor($requestedAsset),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function contentTypeFor(string $path): string
    {
        return match (Str::lower(pathinfo($path, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
    }
}

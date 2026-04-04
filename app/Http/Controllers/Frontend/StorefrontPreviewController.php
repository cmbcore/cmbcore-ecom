<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\StorefrontHomeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Renders a storefront page inside the admin preview iframe.
 *
 * Route: GET /preview-theme/{alias}?_pt={token}&target={target}
 *
 * The ThemePreviewSessionMiddleware has already loaded the draft
 * session into app('theme.preview_session'). Theme helpers
 * (theme_setting, theme_menu) check for this before reading DB.
 */
class StorefrontPreviewController extends Controller
{
    public function __construct(
        private readonly StorefrontHomeService $homeService,
    ) {}

    public function __invoke(Request $request, string $alias): View
    {
        $target = (string) ($request->query('target', 'home'));

        // Pass preview metadata to the view so the theme can
        // show a preview badge and suppress analytics/SEO indexing.
        view()->share('is_preview_mode', true);
        view()->share('preview_alias', $alias);

        return match (true) {
            default => theme_manager()->view('home', $this->homeService->payload()),
        };
    }
}

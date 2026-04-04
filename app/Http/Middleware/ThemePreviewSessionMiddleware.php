<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use App\Core\Theme\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a request carries a valid `_pt` (preview token) query param,
 * load the draft settings from cache and push them into ThemeManager
 * so the storefront renders the draft instead of persisted settings.
 *
 * The middleware is applied ONLY on the /preview-theme/* route.
 */
class ThemePreviewSessionMiddleware
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('_pt');

        if (! is_string($token) || $token === '') {
            return $next($request);
        }

        // Find the session by scanning possible user patterns.
        // Token is 48 chars, globally unique — scan by pattern.
        // We don't know the user_id here (public route), so we
        // use a secondary lookup index stored when the session is created.
        $indexKey = "theme_preview_token:{$token}";
        $fullKey  = Cache::get($indexKey);

        if (is_string($fullKey)) {
            /** @var array<string, mixed>|null $session */
            $session = Cache::get($fullKey);

            if (is_array($session)) {
                $this->applyPreviewOverride($session);
            }
        }

        return $next($request);
    }

    /**
     * @param array<string, mixed> $session
     */
    private function applyPreviewOverride(array $session): void
    {
        // Signal to ThemeManager that preview mode is active.
        // We store the draft in app container so ThemeManager helpers
        // can pick it up via theme_preview_session() helper.
        app()->instance('theme.preview_session', $session);
    }
}

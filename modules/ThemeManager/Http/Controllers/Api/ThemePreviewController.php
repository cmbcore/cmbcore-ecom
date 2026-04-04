<?php

declare(strict_types=1);

namespace Modules\ThemeManager\Http\Controllers\Api;

use App\Core\Theme\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Manages ephemeral theme preview sessions.
 *
 * A preview session holds draft settings + menus for a theme in cache
 * (TTL: 10 min). The storefront preview route reads these settings
 * instead of the persisted DB settings, so the admin can see changes
 * in real time without committing them.
 */
class ThemePreviewController extends Controller
{
    private const TTL_SECONDS = 600; // 10 minutes

    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // POST /api/admin/themes/{alias}/preview-session
    // Create or refresh a preview session for the given theme.
    // Returns a signed preview_url the admin iframe can load.
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request, string $alias): JsonResponse
    {
        $theme = $this->themeManager->find($alias);
        abort_if($theme === null, 404, "Theme [{$alias}] not found.");

        $validated = $request->validate([
            'settings'       => ['nullable', 'array'],
            'menus'          => ['nullable', 'array'],
            'preview_target' => ['nullable', 'string', 'max:200'],
        ]);

        /** @var \App\Models\User $user */
        $user  = $request->user();
        $token = Str::random(48);
        $key   = "theme_preview:{$user->id}:{$token}";

        Cache::put($key, [
            'alias'          => $alias,
            'settings'       => $validated['settings'] ?? null,
            'menus'          => $validated['menus'] ?? null,
            'preview_target' => $validated['preview_target'] ?? 'home',
            'user_id'        => $user->id,
            'expires_at'     => now()->addSeconds(self::TTL_SECONDS)->toIso8601String(),
        ], self::TTL_SECONDS);

        // Secondary index: token → full key (lets public middleware look up without user_id)
        Cache::put("theme_preview_token:{$token}", $key, self::TTL_SECONDS);

        $target    = $validated['preview_target'] ?? 'home';
        $targetUrl = $this->resolvePreviewTargetUrl($alias, $target);

        $previewUrl = url(
            "/preview-theme/{$alias}?_pt={$token}&target=" . urlencode($target),
        );

        return response()->json([
            'success'     => true,
            'preview_url' => $previewUrl,
            'token'       => $token,
            'target_url'  => $targetUrl,
            'expires_in'  => self::TTL_SECONDS,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // DELETE /api/admin/themes/{alias}/preview-session
    // Revoke all active preview sessions for this user's theme.
    // ──────────────────────────────────────────────────────────────────
    public function destroy(Request $request, string $alias): JsonResponse
    {
        // Nothing to revoke explicitly — sessions are short-lived.
        // Future: tag-based cache invalidation per user.
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function resolvePreviewTargetUrl(string $alias, string $target): string
    {
        return match (true) {
            str_starts_with($target, 'product:') => '/' . substr($target, strlen('product:')),
            str_starts_with($target, 'category:') => '/danh-muc/' . substr($target, strlen('category:')),
            str_starts_with($target, 'blog:') => '/blog/' . substr($target, strlen('blog:')),
            default => '/',
        };
    }
}

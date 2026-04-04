<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use RuntimeException;

class PuckRenderService
{
    /**
     * Convert Puck JSON data into rendered HTML.
     *
     * @param  array<string, mixed>|null  $puckData  The Puck data structure {content: [...], root: {...}}
     */
    public function render(?array $puckData): string
    {
        if ($puckData === null || ! isset($puckData['content']) || ! is_array($puckData['content'])) {
            return '';
        }

        $html = '';

        foreach ($puckData['content'] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = trim((string) ($block['type'] ?? ''));

            if ($type === '') {
                continue;
            }

            $props = Arr::where(
                (array) ($block['props'] ?? []),
                static fn (mixed $value, string $key): bool => $key !== 'id' && $value !== null && $value !== '',
            );

            $html .= "\n" . $this->renderBlock($type, $props);
        }

        return trim($html);
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function renderBlock(string $type, array $props): string
    {
        $data = ['props' => $props];

        // 1. Try theme override: pages.puck-blocks.{Type}
        $themeView = 'pages.puck-blocks.' . $type;

        try {
            $resolvedThemeView = theme_view($themeView);

            if (View::exists($resolvedThemeView)) {
                return theme_manager()->view($themeView, $data)->render();
            }
        } catch (RuntimeException) {
            // Theme doesn't override this block, continue to fallback.
        }

        // 2. Plugin view via registered plugin view path (e.g. contact-form::block)
        $pluginView = strtolower(str_replace('_', '-', $type)) . '::block';

        if (View::exists($pluginView)) {
            return view($pluginView, $data)->render();
        }

        // 3. Module fallback: modules/Page/Resources/views/puck-blocks/{Type}.blade.php
        $moduleViewPath = base_path('modules/Page/Resources/views/puck-blocks/' . $type . '.blade.php');

        if (is_file($moduleViewPath)) {
            return view()->file($moduleViewPath, $data)->render();
        }

        return '';
    }
}

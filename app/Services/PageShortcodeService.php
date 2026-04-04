<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Plugin\HookManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use RuntimeException;

class PageShortcodeService
{
    private const SHORTCODE_PATTERN = '/\[cmb_block\s+type="(?<type>[a-z0-9_-]+)"\s+props="(?<props>[^"]*)"\s*\]/i';

    public function __construct(
        private readonly HookManager $hookManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        $builtIn = [
            [
                'type' => 'hero',
                'label' => 'Hero',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
                    ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
                    ['key' => 'primary_label', 'label' => 'Primary button', 'type' => 'text'],
                    ['key' => 'primary_url', 'label' => 'Primary URL', 'type' => 'text'],
                    ['key' => 'secondary_label', 'label' => 'Secondary button', 'type' => 'text'],
                    ['key' => 'secondary_url', 'label' => 'Secondary URL', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'media_text',
                'label' => 'Media + text',
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
                    ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
                    ['key' => 'image_position', 'label' => 'Image position', 'type' => 'select', 'options' => [
                        ['value' => 'left', 'label' => 'Left'],
                        ['value' => 'right', 'label' => 'Right'],
                    ]],
                    ['key' => 'link_label', 'label' => 'Link label', 'type' => 'text'],
                    ['key' => 'link_url', 'label' => 'Link URL', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'cta',
                'label' => 'CTA',
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
                    ['key' => 'primary_label', 'label' => 'Primary button', 'type' => 'text'],
                    ['key' => 'primary_url', 'label' => 'Primary URL', 'type' => 'text'],
                    ['key' => 'secondary_label', 'label' => 'Secondary button', 'type' => 'text'],
                    ['key' => 'secondary_url', 'label' => 'Secondary URL', 'type' => 'text'],
                    ['key' => 'background_image', 'label' => 'Background image', 'type' => 'image'],
                ],
            ],
        ];

        // Allow plugins to add their own block definitions
        return $this->hookManager->applyFilter('page.block_definitions', $builtIn);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function compile(string $content, array $blocks): string
    {
        $body = trim($content);
        $serializedBlocks = collect($blocks)
            ->filter(static fn (mixed $block): bool => is_array($block) && trim((string) ($block['type'] ?? '')) !== '')
            ->map(function (array $block): string {
                $type = trim((string) ($block['type'] ?? ''));
                $props = base64_encode(json_encode((array) ($block['props'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

                return sprintf('[cmb_block type="%s" props="%s"]', $type, $props);
            })
            ->values()
            ->all();

        if ($serializedBlocks === []) {
            return $body;
        }

        return trim($body . "\n\n" . implode("\n\n", $serializedBlocks));
    }

    /**
     * @return array{content:string, blocks:array<int, array<string, mixed>>}
     */
    public function parse(?string $content): array
    {
        $source = (string) $content;
        $blocks = [];

        preg_match_all(self::SHORTCODE_PATTERN, $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = trim((string) ($match['type'] ?? ''));
            $props = json_decode(base64_decode((string) ($match['props'] ?? ''), true) ?: '{}', true);

            if ($type === '' || ! is_array($props)) {
                continue;
            }

            $blocks[] = [
                'type' => $type,
                'props' => $props,
            ];
        }

        $cleaned = trim((string) preg_replace(self::SHORTCODE_PATTERN, '', $source));

        return [
            'content' => $cleaned,
            'blocks' => $blocks,
        ];
    }

    public function render(?string $content): string
    {
        $parsed = $this->parse($content);
        $html = $parsed['content'];

        foreach ($parsed['blocks'] as $block) {
            $type = (string) ($block['type'] ?? '');
            $props = (array) ($block['props'] ?? []);

            if ($type === '') {
                continue;
            }

            $html .= "\n" . $this->renderBlock($type, $props);
        }

        return trim($html);
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function renderBlock(string $type, array $props): string
    {
        $data = [
            'props' => Arr::where($props, static fn (mixed $value): bool => $value !== null && $value !== ''),
        ];

        $themeView = 'pages.blocks.' . $type;
        try {
            $resolvedThemeView = theme_view($themeView);

            if (View::exists($resolvedThemeView)) {
                return theme_manager()->view($themeView, $data)->render();
            }
        } catch (RuntimeException) {
            // Fall back to module shortcode views when the active theme does not override the block.
        }

        $moduleViewPath = base_path('modules/Page/Resources/views/blocks/' . $type . '.blade.php');

        if (is_file($moduleViewPath)) {
            return view()->file($moduleViewPath, $data)->render();
        }

        return '';
    }
}
